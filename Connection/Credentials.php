<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Connection;


use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\ClientCredentialsGrantInterface;

class Credentials implements ClientCredentialsGrantInterface
{
    /**
     * @var string|null
     */
    private $clientId;

    /**
     * @var string|null
     */
    private $clientSecret;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getAuthorizationUrl(): string
    {
        return 'https://hello.world/authorize';
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }
}