<?php

namespace App\Services;

use App\Support\GitCommandResult;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

class GitCommandService
{
    public function run(string $workingDirectory, array $arguments): GitCommandResult
    {
        $this->assertSafeWorkingDirectory($workingDirectory);
        $this->assertAllowedGitArguments($arguments);

        $command = array_merge(['git', '-c', 'safe.directory='.$workingDirectory], $arguments);
        $process = new Process($command, $workingDirectory, $this->gitEnvironment());
        $process->setTimeout(60);
        $process->run();

        return new GitCommandResult(
            implode(' ', array_map('escapeshellarg', $command)),
            $process->getOutput(),
            $process->getErrorOutput(),
            $process->getExitCode() ?? 1,
        );
    }

    public function init(string $workingDirectory): GitCommandResult
    {
        $this->assertSafeWorkingDirectory($workingDirectory, shouldExist: false);

        if (! is_dir($workingDirectory)) {
            mkdir($workingDirectory, 0755, true);
        }

        return $this->run($workingDirectory, ['init']);
    }

    public function writeInitialReadme(string $workingDirectory, string $projectName): void
    {
        $this->assertSafeWorkingDirectory($workingDirectory);
        $readme = rtrim($workingDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'README.md';

        if (! file_exists($readme)) {
            file_put_contents($readme, "# {$projectName}".PHP_EOL);
        }
    }

    private function gitEnvironment(): array
    {
        return array_filter([
            'PATH' => getenv('PATH') ?: null,
            'Path' => getenv('Path') ?: null,
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
            'HOME' => getenv('HOME') ?: getenv('USERPROFILE') ?: null,
            'USERPROFILE' => getenv('USERPROFILE') ?: null,
        ]);
    }

    private function assertSafeWorkingDirectory(string $path, bool $shouldExist = true): void
    {
        if (trim($path) === '') {
            throw new InvalidArgumentException('ローカルパスが空です。');
        }

        if (str_contains($path, '..')) {
            throw new InvalidArgumentException('相対パス「..」は利用できません。');
        }

        if ($shouldExist && ! is_dir($path)) {
            throw new InvalidArgumentException('指定されたフォルダーが存在しません。');
        }
    }

    private function assertAllowedGitArguments(array $arguments): void
    {
        $operation = $arguments[0] ?? '';
        $allowed = [
            'init', 'status', 'branch', 'remote', 'add', 'commit', 'fetch',
            'pull', 'push', 'diff', 'log', 'checkout', 'restore', 'rev-list',
            'merge',
        ];

        if (! in_array($operation, $allowed, true)) {
            throw new InvalidArgumentException('許可されていない Git 操作です。');
        }

        $joined = implode(' ', $arguments);
        $blockedPatterns = [
            'reset --hard',
            'push --force',
            'clean -fd',
            'rebase',
            'filter-branch',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (str_contains($joined, $pattern)) {
                throw new InvalidArgumentException('危険な操作のため実行できません: '.$pattern);
            }
        }
    }
}
