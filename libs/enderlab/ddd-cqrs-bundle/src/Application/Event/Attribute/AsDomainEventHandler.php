<?php

namespace EnderLab\DddCqrsBundle\Application\Event\Attribute;

use Attribute;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsDomainEventHandler extends AsMessageHandler
{
    /** @param string[] $routingKeys */
    public function __construct(
        public array $routingKeys,
        int $priority = 0
    ) {
        parent::__construct($priority);
    }
}
