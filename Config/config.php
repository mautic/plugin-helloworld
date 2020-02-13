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
        'commands' => [],
        'events' => [],
        'forms' => [],
        'helpers' => [],
        'other' => [
            'helloworld.connection.config' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Connection\Config::class,
                'arguments' => [
                    'mautic.integrations.auth_provider.token_persistence_factory',
                ],
            ],
            'helloworld.connection.client' => [
                'class'     => \MauticPlugin\HelloWorldBundle\Connection\Client::class,
                'arguments' => [
                    'mautic.integrations.auth_provider.oauth2twolegged',
                    'mautic.integrations.helper',
                    'helloworld.connection.config',
                ],
            ],
        ],
        'repositories' => [],
        'sync' => [],
        'integrations' => [
            'helloworld.integration' => [
                'class' => \MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'helloworld.integration.configuration' => [
                'class' => \MauticPlugin\HelloWorldBundle\Integration\Support\ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];