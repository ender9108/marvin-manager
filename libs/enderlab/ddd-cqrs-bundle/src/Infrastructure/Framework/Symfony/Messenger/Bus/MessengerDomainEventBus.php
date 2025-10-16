<?php
namespace EnderLab\DddCqrsBundle\Infrastructure\Framework\Symfony\Messenger\Bus;

use EnderLab\DddCqrsBundle\Application\Event\DomainEventBusInterface;
use EnderLab\DddCqrsBundle\Domain\Event\DomainEventInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerDomainEventBus implements DomainEventBusInterface
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(DomainEventInterface $event): void
    {
        $this->messageBus->dispatch(
            new Envelope($event)
                ->with(
                    new DispatchAfterCurrentBusStamp(),
                    new BusNameStamp('domain.event')
                )
        );
    }
}
