<?php

namespace EnderLab\DddCqrsBundle\Application\Exception;

use Exception;
use function Symfony\Component\String\u;

class MissingModelException extends Exception
{
    public function __construct(
        int $id,
        string $className,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            sprintf('Cannot find %s with id %s', $id, $this->getClassName($className)), $code, $previous
        );
    }

    private function getClassName(string $className): string
    {
        $classNameParts = explode('\\', $className);

        return u($classNameParts[count($classNameParts) - 1])->title();
    }
}
