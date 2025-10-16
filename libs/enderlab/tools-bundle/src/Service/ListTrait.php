<?php

namespace EnderLab\ToolsBundle\Service;

use ReflectionClass;

trait ListTrait
{
    private static array $internalCache = [];

    public static function constantsToArray(): array
    {
        if (!empty(self::$internalCache)) {
            return self::$internalCache;
        }

        $class = new ReflectionClass(__CLASS__);
        $constants = $class->getConstants();

        foreach ($constants as $constant) {
            self::$internalCache[$constant] = $constant;
        }

        return self::$internalCache;
    }
}
