<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use kamermans\OAuth2\Exception\AccessTokenRequestException;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use MauticPlugin\HelloWorldBundle\Connection\Config as ConnectionConfig;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use Monolog\Logger;

class Client
{
    private string $apiUrl = 'https://hello.world/api';
    private HttpFactory $httpFactory;
    private Config $config;
    private ConnectionConfig $connectionConfig;
    private Logger $logger;

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
     *
     * @return mixed[]
     */
    public function get(string $objectName, ?\DateTimeInterface $startDateTime, ?\DateTimeInterface $endDateTime, int $page = 1): array
    {
        $client  = $this->getClient();
        $url     = sprintf('%s/%s', $this->apiUrl, $objectName);

        // This imaginary API assumes support to query for created or modified items between two timestamps with native pagination
        $options = [
            'query' => [
                'createdOrModifiedSince'  => $startDateTime ? $startDateTime->getTimestamp() : null,
                'createdOrModifiedBefore' => $endDateTime ? $endDateTime->getTimestamp() : null,
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

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function upsert(string $objectName, array $data): array
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

    /**
     * @return mixed[]
     */
    public function getFields(string $objectName): array
    {
        $client = $this->getClient();
        $url    = sprintf('%s/fields/%s', $this->apiUrl, $objectName);

        try {
            $response = $client->request('GET', $url);
        } catch (AccessTokenRequestException $exception) {
            // Mock an access token since the authorization URL is non-existing
            exit($exception);
        }

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

    /**
     * @throws PluginNotConfiguredException
     * @throws InvalidCredentialsException
     * @throws IntegrationNotFoundException
     */
    private function getClient(): ClientInterface
    {
        // Using a mocked client in order to demonstrate the UI but the "real" code is below
        if (defined('MAUTIC_ENV') && ('dev' === MAUTIC_ENV || 'prod' === MAUTIC_ENV)) {
            return $this->getMockedClient();
        }

        $credentials = $this->getCredentials();
        $config      = $this->getConfig();

        return $this->httpFactory->getClient($credentials, $config);
    }

    private function getMockedClient(): ClientInterface
    {
        return new GuzzleClient(
            [
                'handler' => new MockedHandler(),
            ]
        );
    }
}
