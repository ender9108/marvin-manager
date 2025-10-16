<?php
namespace EnderLab\DddCqrsBundle\Domain\Exception;

use Override;

final class InvalidArgument extends DomainException implements TranslatableExceptionInterface
{
    public function __construct(
        private readonly string $translationId,
        private readonly array $parameters = [],
        ?string $code = null,
    ) {
        parent::__construct($translationId);
        $this->internalCode = $code ?? self::UNKNOWN_ERROR_CODE;
    }

    #[Override]
    public function translationId(): string
    {
        return $this->translationId;
    }

    #[Override]
    public function translationParameters(): array
    {
        return $this->parameters;
    }

    #[Override]
    public function translationDomain(): string
    {
        return 'assert_messages';
    }
}
