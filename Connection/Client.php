<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Exception\InvalidCredentialsException;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;
use Psr\Http\Message\ResponseInterface;

class Client
{
    private $apiUrl = 'https://hello.world/api';

    /**
     * @var HttpFactory
     */
    private $httpFactory;

    /**
     * @var IntegrationsHelper
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    public function __construct(HttpFactory $httpFactory, IntegrationsHelper $helper, Config $config)
    {
        $this->httpFactory = $httpFactory;
        $this->helper      = $helper;
        $this->config      = $config;
    }

    /**
     * @throws GuzzleException
     * @throws PluginNotConfiguredException
     * @throws IntegrationNotFoundException
     * @throws InvalidCredentialsException
     */
    public function post(string $world, array $data): ResponseInterface
    {
        $client = $this->getClient();

        $url = sprintf('%s/%s', $this->apiUrl, $world);

        return $client->request('POST', $url, ['json' => $data]);
    }

    /**
     * @throws PluginNotConfiguredException
     * @throws InvalidCredentialsException
     * @throws IntegrationNotFoundException
     */
    private function getClient(): ClientInterface
    {
        $credentials = $this->getCredentials();
        $config      = $this->getConfig();

        return $this->httpFactory->getClient($credentials, $config);
    }

    /**
     * @throws PluginNotConfiguredException
     * @throws IntegrationNotFoundException
     */
    private function getCredentials(): Credentials
    {
        $apiKeys = $this->getIntegration()->getApiKeys();
        if (empty($apiKeys['client_id']) || empty($apiKeys['client_secret']) || empty($apiKeys['authorization_url'])) {
            throw new PluginNotConfiguredException();
        }

        return new Credentials($apiKeys['client_id'], $apiKeys['client_secret']);
    }

    /**
     * @throws IntegrationNotFoundException
     */
    private function getConfig(): Config
    {
        $this->config->setIntegrationConfiguration($this->getIntegration());

        return $this->config;
    }

    /**
     * @throws IntegrationNotFoundException
     */
    private function getIntegration(): Integration
    {
        $integration = $this->helper->getIntegration(HelloWorldIntegration::NAME);

        return $integration->getIntegrationConfiguration();
    }
}