<?php
namespace EnderLab\DddCqrsBundle\Infrastructure\Framework\Symfony\Messenger\Bus;

use EnderLab\DddCqrsBundle\Application\Command\CommandBusInterface;
use EnderLab\DddCqrsBundle\Application\Command\CommandInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

final readonly class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(CommandInterface $command): void
    {
        $this->messageBus->dispatch(
            $command,
            [new BusNameStamp('command')]
        );
    }
}
