<?php
namespace EnderLab\MarvinManagerBundle\Messenger;

use EnderLab\DddCqrsBundle\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ManagerResponseCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        public array $payload = []
    ) {
    }
}
