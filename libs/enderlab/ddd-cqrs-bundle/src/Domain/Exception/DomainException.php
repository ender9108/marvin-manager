<?php

namespace EnderLab\DddCqrsBundle\Domain\Exception;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    protected const UNKNOWN_ERROR_CODE = 'E9999';

    protected string $internalCode;

    public function getInternalCode(): string
    {
        return $this->internalCode;

    }
}
