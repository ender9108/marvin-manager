<?php
namespace EnderLab\DddCqrsBundle\Infrastructure\Persistence\Doctrine\DomainEventDispatcher;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use EnderLab\DddCqrsBundle\Application\Event\DomainEventBusInterface;
use EnderLab\DddCqrsBundle\Domain\Model\AggregateRoot;
use EnderLab\DddCqrsBundle\Domain\Event\DomainEventInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class DomainEventDispatcher
{
    /** @var DomainEventInterface[] */
    private array $eventsToDispatch = [];

    public function __construct(private readonly DomainEventBusInterface $domainEventBus)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        // On capture insertions, updates et deletions
        $models = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        foreach ($models as $model) {
            if (!$model instanceof AggregateRoot) {
                continue;
            }

            foreach ($model->releaseEvents() as $event) {
                $this->eventsToDispatch[] = $event;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->eventsToDispatch)) {
            return;
        }

        foreach ($this->eventsToDispatch as $event) {
            $this->domainEventBus->dispatch($event);
        }

        $this->eventsToDispatch = [];
    }
}
