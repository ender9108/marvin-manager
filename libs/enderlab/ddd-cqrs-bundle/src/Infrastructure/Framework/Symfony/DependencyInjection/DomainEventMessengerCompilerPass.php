<?php

namespace EnderLab\DddCqrsBundle\Infrastructure\Framework\Symfony\DependencyInjection;

use EnderLab\DddCqrsBundle\Domain\Event\Attribute\AsDomainEvent;
use EnderLab\DddCqrsBundle\Domain\Event\DomainEventInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DomainEventMessengerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('messenger.transport.domain.event')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('enderlab.domain_event_routing_keys');
        $exchangeName = $container->getParameter('ddd_cqrs.exchange_name');
        $queuesConfig = [];

        /** @var DomainEventInterface $className */
        foreach ($taggedServices as $className => $tags) {
            $routingKey = $className::getRoutingKey() ?? null;

            if (!$routingKey) {
                continue;
            }

            // On prend le préfixe en enlevant le dernier segment
            $parts = explode('.', $routingKey);
            $eventName = array_pop($parts); // created, updated, deleted, etc.
            $eventTypes = [$eventName];

            if ($eventName === '*') {
                $eventTypes = AsDomainEvent::EVENTS;
            }

            $queueName = strtr(implode('.', $parts), [
                '$.' => $container->getParameter('ddd_cqrs.queue_prefix')
            ]);

            if (!isset($queuesConfig[$queueName])) {
                $queuesConfig[$queueName] = [
                    'binding_keys' => [],
                ];
            }

            // Ajoute toutes les variations des events (created, updated, deleted)
            foreach ($eventTypes as $eventType) {
                $key = implode('.', $parts) . '.' . $eventType;
                if (!in_array($key, $queuesConfig[$queueName]['binding_keys'], true)) {
                    $queuesConfig[$queueName]['binding_keys'][] = $key;
                }
            }
        }

        // Récupère la définition du transport
        $transportDef = $container->getDefinition('messenger.transport.domain.event');

        // Met à jour l'option "queues"
        $options = $transportDef->getArgument(1) ?? [];
        $options['exchange']['name'] = $exchangeName;
        $options['queues'] = $queuesConfig;

        $transportDef->setArgument(1, $options);
    }
}
