<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Integration\Support;

use Mautic\IntegrationsBundle\Exception\UnauthorizedException;
use Mautic\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface;
use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class AuthSupport extends HelloWorldIntegration implements AuthenticationInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Client $client, Config $config, Session $session, TranslatorInterface $translator)
    {
        $this->client     = $client;
        $this->config     = $config;
        $this->session    = $session;
        $this->translator = $translator;
    }

    public function isAuthenticated(): bool
    {
        return $this->config->isAuthorized();
    }

    /**
     * @throws UnauthorizedException
     */
    public function authenticateIntegration(Request $request): string
    {
        $code  = $request->get('code');
        $state = $request->get('state');

        $this->validateState($state);

        $this->client->exchangeCodeForToken($code, $state);

        return $this->translator->trans('helloworld.auth.success');
    }

    /**
     * @throws UnauthorizedException
     */
    private function validateState(string $givenState): void
    {
        // Fetch the state stored in ConfigSupport::getAuthorizationUrl()
        $expectedState = $this->session->get('helloworld.state');

        // Clear the state
        $this->session->remove('helloworld.state');

        // Validate the state
        if (!$expectedState || $expectedState !== $givenState) {
            throw new UnauthorizedException('State does not match what was expected');
        }
    }
}
