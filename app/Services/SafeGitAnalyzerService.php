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

        return [
            'branch' => trim($branch->stdout) ?: '-',
            'files' => $this->parsePorcelain($status->stdout),
            'remote' => trim($remote->stdout),
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
