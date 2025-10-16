<?php

namespace EnderLab\DddCqrsBundle\Domain\Event;

use DateTimeImmutable;

abstract readonly class AbstractDomainEvent
{
    public readonly DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new DateTimeImmutable();
    }
}
