<?php

namespace EnderLab\MarvinManagerBundle\Messenger\Message;

use EnderLab\DddCqrsBundle\Application\Command\SyncCommandInterface;

final readonly class RestartContainer implements SyncCommandInterface
{
    public function __construct(
        public string $containerId,
        public string $correlationId,
        public int $timeout = 10,
    ) {}
}
