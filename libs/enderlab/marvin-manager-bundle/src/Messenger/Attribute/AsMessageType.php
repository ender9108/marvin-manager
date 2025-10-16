<?php
namespace EnderLab\MarvinManagerBundle\Messenger\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsMessageType
{
    public function __construct(
        public ?string $binding = null,
    ) {
    }
}
