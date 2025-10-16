<?php

namespace MarvinManager\Dto;

use DateTimeInterface;

final readonly class Worker
{
    public function __construct(
        public string $id,
        public string $label,
        public string $processName,
        public string $command,
        public string $type,
        public array $allowedActions = [],
        public int $numProcs = 0,
        public int $priority = 0,
        public bool $autoStart = true,
        public bool $autoRestart = true,
        public ?string $status = null,
        public ?array $metadata = null,
        public ?DateTimeInterface $lastSyncedAt = null,
        public ?DateTimeInterface $updatedAt = null,
        public ?DateTimeInterface $createdAt = null,
    ) {
    }
}
