<?php
namespace EnderLab\MarvinManagerBundle\Messenger;

use EnderLab\DddCqrsBundle\Application\Command\CommandInterface;
use EnderLab\MarvinManagerBundle\Reference\ManagerActionReference;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

final readonly class ManagerRequestCommand implements CommandInterface
{
    public function __construct(
        #[NotBlank]
        public string $containerId,
        #[NotBlank]
        public string $correlationId,
        #[NotBlank]
        #[Choice(choices: [
            ManagerActionReference::ACTION_START->value,
            ManagerActionReference::ACTION_STOP->value,
            ManagerActionReference::ACTION_RESTART->value,
            ManagerActionReference::ACTION_EXEC_CMD->value,
        ])]
        public string $action,
        public ?string $command = null,
        public array $args = [],
        public int $timeout = 10,
    ) {
    }
}
