<?php

declare(strict_types=1);

return [
    'name'        => 'Hello World',
    'description' => 'Example Mautic 2 plugin built on the IntegrationsBundle plugin',
    'version'     => '1.0.1',
    'author'      => 'Acquia, Inc.',
    'routes'      => [
        'main'   => [],
        'public' => [
            'helloworld_mocked_authorization_endpoint' => [
                'path'       => '/helloworld/mock/authorize',
                'controller' => 'HelloWorldBundle:Mocks\Authorize:mock',
                'method'     => 'GET',
            ],
            'helloworld_mocked_token_endpoint' => [
                'path'       => '/helloworld/mock/token',
                'controller' => 'HelloWorldBundle:Mocks\Token:mock',
                'method'     => 'POST',
            ],
            'helloworld_mocked_user_endpoint' => [
                'path'       => '/helloworld/mock/user',
                'controller' => 'HelloWorldBundle:Mocks\User:mock',
                'method'     => 'GET',
            ],
        ],
        'api'    => [],
    ],
    'menu'        => [],
    'services'    => [
        'other'        => [
            // Provides access to configured API keys, settings, field mapping, etc
            'helloworld.config'            => [
                'class'     => \MauticPlugin\HelloWorldBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            // Configuration for the http client which includes where to persist tokens
            'helloworld.connection.config' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Connection\Config::class,
                'arguments' => [
                    'mautic.integrations.auth_provider.token_persistence_factory',
                ],
            ],
            // The http client used to communicate with the integration which in this case uses OAuth2 client_credentials grant
            'helloworld.connection.client' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Connection\Client::class,
                'arguments' => [
                    'mautic.integrations.auth_provider.oauth2threelegged',
                    'helloworld.config',
                    'helloworld.connection.config',
                    'monolog.logger.mautic',
                    'router',
                ],
            ],
        ],
        'sync'         => [
            // Returns available fields from the integration either from cache or "live" via API
            'helloworld.sync.repository.fields'      => [
                'class'     => \MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
                    'helloworld.connection.client',
                ],
            ],
            // Creates the instructions to the sync engine for which objects and fields to sync and direction of data flow
            'helloworld.sync.mapping_manual.factory' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory::class,
                'arguments' => [
                    'helloworld.sync.repository.fields',
                    'helloworld.config',
                ],
            ],
            // Proxies the actions of the sync between Mautic and this integration to the appropriate services
            'helloworld.sync.data_exchange' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Sync\DataExchange\SyncDataExchange::class,
                'arguments' => [
                    'helloworld.sync.data_exchange.report_builder',
                    'helloworld.sync.data_exchange.order_executioner',
                ],
            ],
            // Builds a report of updated and new objects from the integration to sync with Mautic
            'helloworld.sync.data_exchange.report_builder' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Sync\DataExchange\ReportBuilder::class,
                'arguments' => [
                    'helloworld.connection.client',
                    'helloworld.config',
                    'helloworld.sync.repository.fields',
                ],
            ],
            // Pushes updated or new Mautic contacts or companies to the integration
            'helloworld.sync.data_exchange.order_executioner' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Sync\DataExchange\OrderExecutioner::class,
                'arguments' => [
                    'helloworld.connection.client',
                ],
            ],
        ],
        'integrations' => [
            // Basic definitions with name, display name and icon
            'mautic.integration.helloworld'               => [
                'class' => \MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'helloworld.integration.configuration' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Integration\Support\ConfigSupport::class,
                'arguments' => [
                    'helloworld.sync.repository.fields',
                    'helloworld.config',
                    'session',
                    'router',
                    'translator',
                ],
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
            // Defines the mapping manual and sync data exchange service for the sync engine
            'helloworld.integration.sync'          => [
                'class'     => \MauticPlugin\HelloWorldBundle\Integration\Support\SyncSupport::class,
                'arguments' => [
                    'helloworld.sync.mapping_manual.factory',
                    'helloworld.sync.data_exchange',
                ],
                'tags'      => [
                    'mautic.sync_integration',
                ],
            ],
            // Provides the means to exchange a code for a token for the oauth2 authorization code grant
            'helloworld.integration.authorization' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Integration\Support\AuthSupport::class,
                'arguments' => [
                    'helloworld.connection.client',
                    'helloworld.config',
                    'session',
                    'translator',
                ],
                'tags'      => [
                    'mautic.authentication_integration',
                ],
            ],
        ],
        // These are all mocks to simply enable demonstration of the oauth2 flow
        'controllers'  => [
            'helloworld.integration.controller.mocked_authorization' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Controller\Mocks\AuthorizeController::class,
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'helloworld.integration.controller.mocked_token' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Controller\Mocks\TokenController::class,
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'helloworld.integration.controller.mocked_user' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Controller\Mocks\UserController::class,
                'arguments' => [
                    'helloworld.config',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
        ],
    ],
];
