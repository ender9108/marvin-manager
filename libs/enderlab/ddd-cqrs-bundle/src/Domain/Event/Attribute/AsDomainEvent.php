<?php

namespace EnderLab\DddCqrsBundle\Domain\Event\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsDomainEvent
{
    public const string EVENT_CREATED = 'created';
    public const string EVENT_UPDATED = 'updated';
    public const string EVENT_DELETED = 'deleted';

    public const array EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_UPDATED,
        self::EVENT_DELETED,
    ];

    public function __construct(public string $routingKey)
    {
    }
}
