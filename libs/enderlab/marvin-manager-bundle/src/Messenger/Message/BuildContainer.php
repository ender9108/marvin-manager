<?php

namespace EnderLab\MarvinManagerBundle\Messenger\Message;

use EnderLab\DddCqrsBundle\Application\Command\SyncCommandInterface;

final readonly class BuildContainer implements SyncCommandInterface
{
    public function __construct(
        public string $containerId,
        public string $correlationId,
    ) {}
}
