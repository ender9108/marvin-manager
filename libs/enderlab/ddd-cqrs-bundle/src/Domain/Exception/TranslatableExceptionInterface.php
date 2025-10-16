<?php

namespace EnderLab\DddCqrsBundle\Domain\Exception;

use Throwable;

interface TranslatableExceptionInterface extends Throwable
{
    public function translationId(): string;

    public function translationParameters(): array;

    public function translationDomain(): string;
}
