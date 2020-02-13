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
    private $config;

    /**
     * @var ConnectionConfig
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
    public function getList(string $objectName, \DateTimeInterface $startDateTime, \DateTimeInterface $endDateTime, int $page): array
    {
        $client  = $this->getClient();
        $url     = sprintf('%s/%s', $this->apiUrl, $objectName);

        // This imaginary API assumes support to query for created or modified items between two timestamps with native pagination
        $options = [
            'query' => [
                'createdOrModifiedSince'  => $startDateTime->getTimestamp(),
                'createdOrModifiedBefore' => $endDateTime->getTimestamp(),
                'page'                    => $page,
            ],
        ];

        $response = $client->request('GET', $url, $options);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error fetching %s objects: %s',
                    HelloWorldIntegration::DISPLAY_NAME,
                    $objectName,
                    $response->getReasonPhrase()
                )
            );

            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    public function upsertList(string $objectName, array $data): array
    {
        $client  = $this->getClient();
        $url     = sprintf('%s/%s', $this->apiUrl, $objectName);
        $options = ['json' => $data];

        // This imaginary API assumes a native upsert feature that returns respones in a batch format
        $response = $client->request('POST', $url, $options);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error upserting %s objects: %s',
                    HelloWorldIntegration::DISPLAY_NAME,
                    $objectName,
                    $response->getReasonPhrase()
                )
            );

            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getFields(string $objectName): array
    {
        $client = $this->getClient();
        $url      = sprintf('%s/world/%s', $this->apiUrl, $objectName);

        $response = $client->request('GET', $url);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error fetching %s fields: %s',
                    HelloWorldIntegration::DISPLAY_NAME,
                    $objectName,
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
    private function getConfig(): ConnectionConfig
    {
        $this->connectionConfig->setIntegrationConfiguration($this->config->getIntegrationEntity());

        return $this->connectionConfig;
    }
}