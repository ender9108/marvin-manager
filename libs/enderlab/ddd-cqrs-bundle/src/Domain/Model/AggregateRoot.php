<?php

namespace EnderLab\DddCqrsBundle\Domain\Model;

use EnderLab\DddCqrsBundle\Domain\Event\DomainEventInterface;

abstract class AggregateRoot
{

    /** @var DomainEventInterface[] */
    private array $recordedEvents = [];

    protected function recordThat(DomainEventInterface $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return DomainEventInterface[]
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
