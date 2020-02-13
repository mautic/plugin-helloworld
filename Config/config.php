<?php
return [
    'name'        => 'Hello World',
    'description' => 'Example Mautic 2 plugin built on the IntegrationsBundle plugin',
    'version'     => '1.0.1',
    'author'      => 'Acquia, Inc.',
    'routes'      => [
        'main' => [],
        'public' => [],
        'api' => [],
    ],
    'menu' => [],
    'services' => [
        'other' => [
            // Provides access to configured API keys, settings, field mapping, etc
            'helloworld.config' => [
                'class' => \MauticPlugin\HelloWorldBundle\Integration\Config::class,
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
                    'mautic.integrations.auth_provider.oauth2twolegged',
                    'helloworld.connection.config',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'sync' => [
            // Returns available fields from the integration either from cache or "live" via API
            'helloworld.sync.repository.fields' => [
                'class' => \MauticPlugin\HelloWorldBundle\Sync\Field\FieldRepository::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
                    'helloworld.connection.client',
                ],
            ],
            // Creates the instructions to the sync engine for which objects and fields to sync and direction of data flow
            'helloworld.sync.mapping_manual.factory' => [
                'class' => \MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory::class,
                'arguments' => [
                    'helloworld.sync.repository.fields',
                    'helloworld.config',
                ],
            ]
        ],
        'integrations' => [
            // Basic definitions with name, display name and icon
            'helloworld.integration' => [
                'class' => \MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'helloworld.integration.configuration' => [
                'class' => \MauticPlugin\HelloWorldBundle\Integration\Support\ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];