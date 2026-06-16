<?php

namespace App\Http\Controllers;

use App\Models\SafeGitOperationLog;
use App\Models\SafeGitRepository;
use App\Services\GitCommandService;
use App\Services\SafeGitAnalyzerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
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
            return back()->with('error', 'origin はすでに設定されています。URL を変更する場合は「remote set-url origin」を選んでください。');
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

    public function unstage(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1000'],
        ]);

        return $this->execute($request, $repository, 'unstage', fn () =>
            $this->git->run($repository->local_path, ['restore', '--staged', '--', $data['path']])
        );
    }

    public function discard(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1000'],
            'untracked' => ['nullable', 'boolean'],
        ]);

        return $this->execute($request, $repository, 'discard', fn () =>
            $request->boolean('untracked')
                ? $this->git->run($repository->local_path, ['clean', '-f', '--', $data['path']])
                : $this->git->run($repository->local_path, ['restore', '--', $data['path']])
        );
    }

    public function commit(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        if (! $this->hasStagedChanges($repository)) {
            return back()->with('error', 'ステージ済みファイルがありません。commit 前に add してください。');
        }

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

        if ($this->hasWorkingTreeChanges($repository) && ! $request->boolean('confirm_dirty_pull')) {
            return back()->with('error', '未コミットの変更があります。pull する場合は確認チェックを入れるか、先に commit / stash してください。');
        }

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

    public function createBranch(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'branch' => ['required', 'string', 'max:255'],
            'checkout' => ['nullable', 'boolean'],
        ]);

        $branch = $this->branchName($data['branch']);

        return $this->execute($request, $repository, 'branch_create', function () use ($request, $repository, $branch) {
            $result = $this->git->run($repository->local_path, ['branch', $branch]);

            if ($result->successful() && $request->boolean('checkout')) {
                return $this->git->run($repository->local_path, ['checkout', $branch]);
            }

            return $result;
        });
    }

    public function switchBranch(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'branch' => ['required', 'string', 'max:255'],
        ]);

        $branch = $this->branchName($data['branch']);

        return $this->execute($request, $repository, 'branch_switch', fn () =>
            $this->git->run($repository->local_path, ['checkout', $branch])
        );
    }

    public function checkoutRemoteBranch(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'remote_branch' => ['required', 'string', 'max:255'],
            'local_branch' => ['required', 'string', 'max:255'],
        ]);

        $remoteBranch = $this->branchName($data['remote_branch']);
        $localBranch = $this->branchName($data['local_branch']);

        return $this->execute($request, $repository, 'branch_checkout_remote', fn () =>
            $this->git->run($repository->local_path, ['checkout', '-b', $localBranch, $remoteBranch])
        );
    }

    public function deleteBranch(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'branch' => ['required', 'string', 'max:255'],
            'confirm_delete_protected' => ['nullable', 'boolean'],
        ]);

        $branch = $this->branchName($data['branch']);
        $currentBranch = $this->currentBranch($repository);

        if ($branch === $currentBranch) {
            return back()->with('error', '現在使用中のブランチは削除できません。先に別のブランチへ切り替えてください。');
        }

        if (in_array($branch, ['main', 'master'], true) && ! $request->boolean('confirm_delete_protected')) {
            return back()->with('error', 'main/master を削除するには確認チェックが必要です。');
        }

        return $this->execute($request, $repository, 'branch_delete', fn () =>
            $this->git->run($repository->local_path, ['branch', '-d', $branch])
        );
    }

    public function mergeBranch(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'branch' => ['required', 'string', 'max:255'],
        ]);

        $branch = $this->branchName($data['branch']);
        $currentBranch = $this->currentBranch($repository);

        if ($branch === $currentBranch) {
            return back()->with('error', '現在のブランチ自身はマージできません。');
        }

        return $this->execute($request, $repository, 'branch_merge', fn () =>
            $this->git->run($repository->local_path, ['merge', '--no-ff', $branch])
        );
    }

    public function createStash(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->hasWorkingTreeChanges($repository)) {
            return back()->with('error', 'stash する変更がありません。');
        }

        $message = trim($data['message'] ?? '') ?: 'Safe Git Manager stash';

        return $this->execute($request, $repository, 'stash_create', fn () =>
            $this->git->run($repository->local_path, ['stash', 'push', '-u', '-m', $message])
        );
    }

    public function applyStash(Request $request, SafeGitRepository $repository): RedirectResponse
    {
        $data = $request->validate([
            'stash' => ['required', 'string', 'max:100'],
            'mode' => ['required', 'in:apply,pop'],
        ]);

        return $this->execute($request, $repository, 'stash_'.$data['mode'], fn () =>
            $this->git->run($repository->local_path, ['stash', $data['mode'], $data['stash']])
        );
    }

    public function diff(Request $request, SafeGitRepository $repository): View
    {
        $path = trim((string) $request->query('path', ''));
        $cached = $request->boolean('cached');
        $args = ['diff'];

        if ($cached) {
            $args[] = '--cached';
        }

        if ($path !== '') {
            $args[] = '--';
            $args[] = $path;
        }

        $result = $this->git->run($repository->local_path, $args);

        return view('safe-git-manager.repositories.diff', compact('repository', 'result', 'path', 'cached'));
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

    private function hasStagedChanges(SafeGitRepository $repository): bool
    {
        return $this->git->run($repository->local_path, ['diff', '--cached', '--quiet'])->exitCode === 1;
    }

    private function hasWorkingTreeChanges(SafeGitRepository $repository): bool
    {
        return trim($this->git->run($repository->local_path, ['status', '--porcelain'])->stdout) !== '';
    }

    private function branchName(string $branch): string
    {
        $branch = trim($branch);

        if ($branch === '') {
            throw new InvalidArgumentException('ブランチ名を入力してください。');
        }

        return $branch;
    }
}
