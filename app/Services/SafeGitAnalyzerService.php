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

        return [
            'branch' => trim($branch->stdout) ?: '-',
            'branches' => $this->parseBranches($branches->stdout),
            'files' => $this->parsePorcelain($status->stdout),
            'remote' => trim($remote->stdout),
            'remotes' => $this->parseRemotes($remote->stdout),
            'conflicts' => array_values(array_filter(explode("\n", trim($conflicts->stdout)))),
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

    private function parsePorcelain(string $output): array
    {
        $files = [];

        foreach (array_filter(explode("\n", rtrim($output))) as $line) {
            $line = rtrim($line, "\r");
            $status = substr($line, 0, 2);
            $path = trim(substr($line, 3));

            $files[] = [
                'status' => $this->labelStatus($status),
                'raw_status' => $status,
                'path' => $path,
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

    private function labelStatus(string $status): string
    {
        return match (trim($status)) {
            'M' => 'Modified',
            'A' => 'Added',
            'D' => 'Deleted',
            '??' => 'Untracked',
            default => $status,
        };
    }
}
