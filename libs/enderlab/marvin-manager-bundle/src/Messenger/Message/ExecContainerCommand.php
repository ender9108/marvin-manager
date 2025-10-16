<?php

namespace EnderLab\MarvinManagerBundle\Messenger\Message;

use EnderLab\DddCqrsBundle\Application\Command\SyncCommandInterface;

final readonly class ExecContainerCommand implements SyncCommandInterface
{
    public function __construct(
        public string $containerId,
        public string $correlationId,
        public string $command,
        public array $args = [],
    ) {}
}
