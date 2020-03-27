<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;

use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\CodeInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\CredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\RedirectUriInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\ScopeInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\StateInterface;

class Credentials implements CredentialsInterface, CodeInterface, StateInterface, RedirectUriInterface, ScopeInterface
{
    /**
     * @var string
     */
    private $authorizationUrl;

    /**
     * @var string
     */
    private $tokenUrl;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string|null
     */
    private $clientId;

    /**
     * @var string|null
     */
    private $clientSecret;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $state;

    public function __construct(string $authorizationUrl, string $tokenUrl, string $redirectUri, string $clientId, string $clientSecret, ?string $code, ?string $state)
    {
        $this->authorizationUrl = $authorizationUrl;
        $this->tokenUrl         = $tokenUrl;
        $this->redirectUri      = $redirectUri;
        $this->clientId         = $clientId;
        $this->clientSecret     = $clientSecret;
        $this->code             = $code;
        $this->state            = $state;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }

    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getScope(): ?string
    {
        return 'api';
    }
}
