<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Exception\InvalidCredentialsException;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Connection\Config as ConnectionConfig;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class Client
{
    private $apiUrl = 'https://hello.world/api';

    /**
     * @var HttpFactory
     */
    private $httpFactory;

    /**
     * @var Config
     */
    private $connectionConfig;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(HttpFactory $httpFactory, Config $config, ConnectionConfig $connectionConfig, Logger $logger)
    {
        $this->httpFactory      = $httpFactory;
        $this->config           = $config;
        $this->connectionConfig = $connectionConfig;
        $this->logger           = $logger;
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

    public function getFields(string $object): array
    {
        $client = $this->getClient();

        $url      = sprintf('%s/world/%s', $this->apiUrl, $object);
        $response = $client->request('GET', $url);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error fetching %s fields: %s',
                    HelloWorldIntegration::DISPLAY_NAME,
                    $object,
                    $response->getReasonPhrase()
                )
            );

            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
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
     */
    private function getCredentials(): Credentials
    {
        if (!$this->config->isConfigured()) {
            throw new PluginNotConfiguredException();
        }
        $apiKeys = $this->config->getApiKeys();

        return new Credentials($apiKeys['client_id'], $apiKeys['client_secret']);
    }

    /**
     * @throws IntegrationNotFoundException
     */
    private function getConfig(): Config
    {
        $this->connectionConfig->setIntegrationConfiguration($this->config->getIntegrationEntity());

        return $this->connectionConfig;
    }
}