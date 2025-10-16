<?php

namespace EnderLab\MarvinManagerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MarvinManagerBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Check if we are in marvin-core or marvin-manager
        if ($builder->hasParameter('shared.app_name') === false) {
            if ($builder->hasParameter('env(MESSENGER_TRANSPORT_DSN)')) {
                $builder->prependExtensionConfig('framework', [
                    'messenger' => [
                        'transports' => [
                            'marvin.to.manager' => [
                                'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%',
                                'options' => [
                                    'auto_setup' => true,
                                    'exchange' => [
                                        'name' => 'marvin.to.manager',
                                        'type' => 'direct',
                                        'default_publish_routing_key' => 'marvin.to.manager.request'
                                    ],
                                    'queues' => [
                                        'manager.to.marvin.response' => [
                                            'binding_keys' => 'manager.to.marvin.response'
                                        ]
                                    ]
                                ],
                                'retry_strategy' => [
                                    'delay' => 500,
                                ]
                            ],
                        ],
                        'routing' => [
                            'EnderLab\\MarvinManagerBundle\\System\\Infrastructure\\Framework\\Symfony\\Messenger\\ManagerRequestCommand' => 'marvin.to.manager'
                        ]
                    ]
                ]);
            }
        }
    }
}
