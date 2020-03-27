<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use MauticPlugin\HelloWorldBundle\Connection\Config as ConnectionConfig;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use Monolog\Logger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

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

    /**
     * @var Router
     */
    private $router;

    public function __construct(HttpFactory $httpFactory, Config $config, ConnectionConfig $connectionConfig, Logger $logger, Router $router)
    {
        $this->httpFactory      = $httpFactory;
        $this->config           = $config;
        $this->connectionConfig = $connectionConfig;
        $this->logger           = $logger;
        $this->router           = $router;
    }

    /**
     * @throws GuzzleException
     * @throws PluginNotConfiguredException
     * @throws IntegrationNotFoundException
     * @throws InvalidCredentialsException
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

    public function getFields(string $objectName): array
    {
        $client = $this->getClient();
        $url    = sprintf('%s/fields/%s', $this->apiUrl, $objectName);

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
     * Used by AuthSupport to exchange a code for tokens.
     */
    public function exchangeCodeForToken(string $code, string $state): void
    {
        $client = $this->getClientForAuthorization($code, $state);

        // Force the client to make a call so that the Guzzle middleware will exchange the code for an access token
        $client->request(
            'GET',
            $this->router->generate('helloworld_mocked_user_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
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

        // This is the real code that should be used in the plugin
        $credentials = $this->getCredentials();
        $config      = $this->getConfig();

        return $this->httpFactory->getClient($credentials, $config);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws InvalidCredentialsException
     * @throws PluginNotConfiguredException
     */
    private function getClientForAuthorization(?string $code = null, ?string $state = null): ClientInterface
    {
        $credentials = $this->getCredentials($code, $state);
        $config      = $this->getConfig();

        return $this->httpFactory->getClient($credentials, $config);
    }

    /**
     * @throws PluginNotConfiguredException
     */
    private function getCredentials(?string $code = null, ?string $state = null): Credentials
    {
        if (!$this->config->isConfigured()) {
            throw new PluginNotConfiguredException();
        }
        $apiKeys = $this->config->getApiKeys();

        $redirectUri = $this->router->generate(
            'mautic_integration_public_callback',
            ['integration' => HelloWorldIntegration::NAME],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // These are mocked just to demonstrate the Oauth2 flow but likely would be hard coded in the Credentials class unless it needs to be
        // dynamically set.
        $mockedAuthorizationUrl = $this->router->generate('helloworld_mocked_authorization_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $mockedTokenUrl         = $this->router->generate('helloworld_mocked_token_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return new Credentials(
            $mockedAuthorizationUrl, $mockedTokenUrl, $redirectUri, $apiKeys['client_id'], $apiKeys['client_secret'], $code, $state
        );
    }

    /**
     * @throws IntegrationNotFoundException
     */
    private function getConfig(): ConnectionConfig
    {
        $this->connectionConfig->setIntegrationConfiguration($this->config->getIntegrationEntity());

        return $this->connectionConfig;
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
