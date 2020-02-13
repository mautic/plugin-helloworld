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
        'other' => [],
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