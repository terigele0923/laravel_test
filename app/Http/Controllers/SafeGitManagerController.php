<?php

namespace App\Http\Controllers;

use App\Models\SafeGitOperationLog;
use App\Models\SafeGitRepository;
use App\Services\GitCommandService;
use App\Services\SafeGitAnalyzerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SafeGitManagerController extends Controller
{
    public function __construct(
        private readonly GitCommandService $git,
        private readonly SafeGitAnalyzerService $analyzer,
    ) {}

    public function index(): View
    {
        $repositories = SafeGitRepository::latest()->paginate(20);

        return view('safe-git-manager.repositories.index', compact('repositories'));
    }

    public function create(): View
    {
        return view('safe-git-manager.repositories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'local_path' => ['required', 'string', 'max:1000'],
            'remote_url' => ['nullable', 'url', 'max:1000'],
            'default_branch' => ['required', 'string', 'max:100'],
        ]);

        $repository = SafeGitRepository::create([
            'user_id' => optional($request->user())->id,
            'name' => $data['name'],
            'local_path' => $data['local_path'],
            'remote_name' => 'origin',
            'remote_url' => $data['remote_url'] ?? null,
            'default_branch' => $data['default_branch'],
        ]);

        return redirect()->route('safe-git.repositories.show', $repository)
            ->with('success', 'リポジトリを登録しました。');
    }

    public function show(SafeGitRepository $repository): View
    {
        $status = [];
        $graph = '';

        try {
            if (is_dir($repository->local_path.'/.git')) {
                $status = $this->analyzer->status($repository);
                $graph = $this->analyzer->logGraph($repository);
            }
        } catch (Throwable $e) {
            $status['error'] = $e->getMessage();
        }

        return view('safe-git-manager.repositories.show', compact('repository', 'status', 'graph'));
    }

    public function destroy(SafeGitRepository $repository): RedirectResponse
    {
        $name = $repository->name;
        $repository->delete();

        return redirect()->route('safe-git.repositories.index')
            ->with('success', "「{$name}」の登録を削除しました。ローカルフォルダと GitHub リポジトリは削除していません。");
    }

    public function init(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        return $this->execute($request, $repository, 'init', function () use ($request, $repository) {
            $result = $this->git->init($repository->local_path);

            if ($request->boolean('create_readme')) {
                $this->git->writeInitialReadme($repository->local_path, $repository->name);
            }

            if ($result->successful()) {
                $this->git->run($repository->local_path, ['branch', '-M', $repository->default_branch]);
            }

            return $result;
        });
    }

    public function remote(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'remote_url' => ['required', 'url', 'max:1000'],
            'mode' => ['required', 'in:add,set-url'],
        ]);

        $originExists = $this->originExists($repository);

        if ($data['mode'] === 'add' && $originExists) {
            return back()->with('error', 'origin はすでに設定されています。URLを変更する場合は「remote set-url origin」を選んでください。');
        }

        if ($data['mode'] === 'set-url' && ! $originExists) {
            return back()->with('error', 'origin はまだ設定されていません。初回は「remote add origin」を選んでください。');
        }

        return $this->execute($request, $repository, 'remote_'.$data['mode'], function () use ($data, $repository) {
            $args = $data['mode'] === 'add'
                ? ['remote', 'add', 'origin', $data['remote_url']]
                : ['remote', 'set-url', 'origin', $data['remote_url']];

            $result = $this->git->run($repository->local_path, $args);

            if ($result->successful()) {
                $repository->update([
                    'remote_name' => 'origin',
                    'remote_url' => $data['remote_url'],
                ]);
            }

            return $result;
        });
    }

    public function add(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1000'],
        ]);

        return $this->execute($request, $repository, 'add', fn () =>
            $this->git->run($repository->local_path, ['add', $data['path']])
        );
    }

    public function commit(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        return $this->execute($request, $repository, 'commit', fn () =>
            $this->git->run($repository->local_path, ['commit', '-m', $data['message']])
        );
    }

    public function fetch(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        return $this->execute($request, $repository, 'fetch', fn () =>
            $this->git->run($repository->local_path, ['fetch', 'origin'])
        );
    }

    public function pull(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $branch = $this->currentBranch($repository);

        return $this->execute($request, $repository, 'pull', fn () =>
            $this->git->run($repository->local_path, ['pull', 'origin', $branch])
        );
    }

    public function push(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $branch = $this->currentBranch($repository);

        if (in_array($branch, ['main', 'master'], true) && ! $request->boolean('confirm_main_push')) {
            return back()->with('error', 'main/master への直接 push は慎重に行う必要があります。確認チェックを入れてから実行してください。');
        }

        return $this->execute($request, $repository, 'push', fn () =>
            $this->git->run($repository->local_path, ['push', 'origin', $branch])
        );
    }

    public function diff(SafeGitRepository $repository): View
    {
        $result = $this->git->run($repository->local_path, ['diff']);

        return view('safe-git-manager.repositories.diff', compact('repository', 'result'));
    }

    public function logs(SafeGitRepository $repository): View
    {
        $logs = $repository->logs()->latest()->paginate(30);

        return view('safe-git-manager.logs.index', compact('repository', 'logs'));
    }

    private function execute(Request $request, SafeGitRepository $repository, string $operation, callable $callback): RedirectResponse
    {
        try {
            $result = $callback();

            SafeGitOperationLog::create([
                'user_id' => optional($request->user())->id,
                'repository_id' => $repository->id,
                'operation' => $operation,
                'command' => $result->command,
                'status' => $result->successful() ? 'success' : 'failed',
                'stdout' => $result->stdout,
                'stderr' => $result->stderr,
                'exit_code' => $result->exitCode,
            ]);

            return back()->with(
                $result->successful() ? 'success' : 'error',
                $result->successful() ? '実行しました。' : '実行に失敗しました。詳細は操作ログを確認してください。'
            );
        } catch (Throwable $e) {
            SafeGitOperationLog::create([
                'user_id' => optional($request->user())->id,
                'repository_id' => $repository->id,
                'operation' => $operation,
                'command' => '-',
                'status' => 'failed',
                'stdout' => '',
                'stderr' => $e->getMessage(),
                'exit_code' => 1,
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    private function currentBranch(SafeGitRepository $repository): string
    {
        $result = $this->git->run($repository->local_path, ['branch', '--show-current']);

        return trim($result->stdout) ?: $repository->default_branch;
    }

    private function originExists(SafeGitRepository $repository): bool
    {
        return $this->git->run($repository->local_path, ['remote', 'get-url', 'origin'])->successful();
    }
}
