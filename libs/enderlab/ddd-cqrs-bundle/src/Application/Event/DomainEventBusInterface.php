<?php

namespace EnderLab\DddCqrsBundle\Application\Event;

use EnderLab\DddCqrsBundle\Domain\Event\DomainEventInterface;

interface DomainEventBusInterface
{
    public function dispatch(DomainEventInterface $event): void;
}
