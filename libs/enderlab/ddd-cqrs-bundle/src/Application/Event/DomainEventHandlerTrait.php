<?php

namespace EnderLab\DddCqrsBundle\Application\Event;

use EnderLab\DddCqrsBundle\Domain\Event\DomainEventInterface;

trait DomainEventHandlerTrait
{
    public static function supports(string $routingKey): bool
    {
        return in_array($routingKey, self::$routingKeys, true);
    }
}
