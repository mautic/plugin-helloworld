<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;

use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenPersistenceInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory;

class Config implements ConfigTokenPersistenceInterface
{
    /**
     * @var TokenPersistenceFactory
     */
    private $tokenPersistenceFactory;

    /**
     * @var Integration
     */
    private $integrationConfiguration;

    public function __construct(TokenPersistenceFactory $tokenPersistenceFactory)
    {
        $this->tokenPersistenceFactory = $tokenPersistenceFactory;
    }

    public function getTokenPersistence(): TokenPersistenceInterface
    {
        return $this->tokenPersistenceFactory->create($this->integrationConfiguration);
    }

    public function setIntegrationConfiguration(Integration $integrationConfiguration): void
    {
        $this->integrationConfiguration = $integrationConfiguration;
    }
}
