<?php

namespace MarvinManager\Dto;

use DateTimeInterface;

final readonly class ContainerDto
{
    public function __construct(
        public string $id,
        public string $label,
        public string $dockerLabel,
        public string $image,
        public string $type,
        public string $status,
        public array $allowedActions = [],
        public array $ports = [],
        public array $volumes = [],
        public array $metadata = [],
        public ?DateTimeInterface $lastSyncedAt = null,
        public ?DateTimeInterface $updatedAt = null,
        public ?DateTimeInterface $createdAt = null,
    ) {
    }
}
