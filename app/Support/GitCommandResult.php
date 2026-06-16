<?php

namespace App\Support;

class GitCommandResult
{
    public function __construct(
        public readonly string $command,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly int $exitCode,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}
