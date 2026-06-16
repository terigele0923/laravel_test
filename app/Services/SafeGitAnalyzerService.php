<?php

namespace App\Services;

use App\Models\SafeGitRepository;

class SafeGitAnalyzerService
{
    public function __construct(private readonly GitCommandService $git) {}

    public function status(SafeGitRepository $repository): array
    {
        $branch = $this->git->run($repository->local_path, ['branch', '--show-current']);
        $status = $this->git->run($repository->local_path, ['status', '--porcelain']);
        $remote = $this->git->run($repository->local_path, ['remote', '-v']);
        $conflicts = $this->git->run($repository->local_path, ['diff', '--name-only', '--diff-filter=U']);
        $branches = $this->git->run($repository->local_path, ['branch', '--format=%(HEAD)|%(refname:short)']);
        $remoteBranches = $this->git->run($repository->local_path, ['branch', '-r', '--format=%(refname:short)']);
        $history = $this->git->run($repository->local_path, ['log', '--date=short', '--pretty=format:%h|%ad|%an|%s', '-30']);
        $stashes = $this->git->run($repository->local_path, ['stash', 'list']);
        $files = $this->parsePorcelain($status->stdout);

        return [
            'branch' => trim($branch->stdout) ?: '-',
            'branches' => $this->parseBranches($branches->stdout),
            'remote_branches' => $this->parseRemoteBranches($remoteBranches->stdout),
            'files' => $files,
            'staged_files' => array_values(array_filter($files, fn ($file) => $file['staged'])),
            'unstaged_files' => array_values(array_filter($files, fn ($file) => $file['unstaged'])),
            'remote' => trim($remote->stdout),
            'remotes' => $this->parseRemotes($remote->stdout),
            'conflicts' => array_values(array_filter(explode("\n", trim($conflicts->stdout)))),
            'history' => $this->parseHistory($history->stdout),
            'stashes' => $this->parseStashes($stashes->stdout),
        ];
    }

    public function logGraph(SafeGitRepository $repository): string
    {
        $result = $this->git->run($repository->local_path, [
            'log', '--oneline', '--graph', '--decorate', '--all', '-30',
        ]);

        return trim($result->stdout) ?: $result->stderr;
    }

    private function parseBranches(string $output): array
    {
        $branches = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            $line = rtrim($line, "\r");
            [$head, $name] = array_pad(explode('|', $line, 2), 2, '');
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $branches[] = [
                'name' => $name,
                'current' => trim($head) === '*',
                'protected' => in_array($name, ['main', 'master'], true),
            ];
        }

        return $branches;
    }

    private function parseRemoteBranches(string $output): array
    {
        $branches = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            $name = trim(rtrim($line, "\r"));

            if ($name === '' || str_ends_with($name, '/HEAD')) {
                continue;
            }

            $localName = str_contains($name, '/') ? substr($name, strpos($name, '/') + 1) : $name;

            $branches[] = [
                'name' => $name,
                'local_name' => $localName,
            ];
        }

        return $branches;
    }

    private function parsePorcelain(string $output): array
    {
        $files = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            $line = rtrim($line, "\r");
            $indexStatus = substr($line, 0, 1);
            $workTreeStatus = substr($line, 1, 1);
            $rawStatus = substr($line, 0, 2);
            $path = trim(substr($line, 3));
            $isUntracked = $rawStatus === '??';

            $files[] = [
                'status' => $this->labelStatus($rawStatus),
                'raw_status' => $rawStatus,
                'index_status' => $indexStatus,
                'worktree_status' => $workTreeStatus,
                'path' => $path,
                'staged' => ! $isUntracked && trim($indexStatus) !== '',
                'unstaged' => $isUntracked || trim($workTreeStatus) !== '',
                'untracked' => $isUntracked,
            ];
        }

        return $files;
    }

    private function parseRemotes(string $output): array
    {
        $remotes = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            $line = trim($line);

            if (! preg_match('/^(\S+)\s+(\S+)\s+\((fetch|push)\)$/', $line, $matches)) {
                continue;
            }

            [, $name, $url, $type] = $matches;
            $remotes[$name][$type] = $url;
        }

        return $remotes;
    }

    private function parseHistory(string $output): array
    {
        $history = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            [$hash, $date, $author, $message] = array_pad(explode('|', rtrim($line, "\r"), 4), 4, '');

            $history[] = compact('hash', 'date', 'author', 'message');
        }

        return $history;
    }

    private function parseStashes(string $output): array
    {
        $stashes = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            $line = rtrim($line, "\r");
            [$ref, $message] = array_pad(explode(': ', $line, 2), 2, '');

            $stashes[] = [
                'ref' => $ref,
                'message' => $message,
            ];
        }

        return $stashes;
    }

    private function labelStatus(string $status): string
    {
        return match (trim($status)) {
            'M' => 'Modified',
            'A' => 'Added',
            'D' => 'Deleted',
            'R' => 'Renamed',
            'C' => 'Copied',
            'U' => 'Unmerged',
            '??' => 'Untracked',
            default => trim($status) ?: 'Changed',
        };
    }
}
