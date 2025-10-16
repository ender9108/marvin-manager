<?php
namespace EnderLab\MarvinManagerBundle\Messenger;

use EnderLab\DddCqrsBundle\Application\Command\CommandInterface;
use EnderLab\MarvinManagerBundle\Reference\ManagerActionReference;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Choice;

final readonly class ManagerResponseCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        public string $correlationId,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['container', 'worker'])]
        public string $entityType,
        #[Assert\NotBlank]
        public string $entityId,
        #[Assert\NotBlank]
        #[Choice(choices: [
            ManagerActionReference::ACTION_START->value,
            ManagerActionReference::ACTION_START_ALL->value,
            ManagerActionReference::ACTION_STOP->value,
            ManagerActionReference::ACTION_STOP_ALL->value,
            ManagerActionReference::ACTION_RESTART->value,
            ManagerActionReference::ACTION_RESTART_ALL->value,
            ManagerActionReference::ACTION_BUILD->value,
            ManagerActionReference::ACTION_BUILD_ALL->value,
            ManagerActionReference::ACTION_EXEC_CMD->value,
        ])]
        public string $action,
        public bool $success = false,
        public ?string $output = null,
        public ?string $error = null,
        public ?array $metadata = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return !$this->success;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }
}
