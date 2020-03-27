<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Connection;

use MauticPlugin\HelloWorldBundle\Connection\Credentials;

class CredentialsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $clientId = 'foo';
        $clientSecret = 'bar';

        $credentials = new Credentials(
            'https://hello.world/authorize',
            'https://hello.world/token',
            'https://hello.world/callback',
            $clientId,
            $clientSecret,
            'abc',
            '123'
        );

        $this->assertEquals($clientId, $credentials->getClientId());
        $this->assertEquals($clientSecret, $credentials->getClientSecret());
        $this->assertEquals('https://hello.world/authorize', $credentials->getAuthorizationUrl());
        $this->assertEquals('https://hello.world/token', $credentials->getTokenUrl());
        $this->assertEquals('https://hello.world/callback', $credentials->getRedirectUri());
        $this->assertEquals('abc', $credentials->getCode());
        $this->assertEquals('123', $credentials->getState());
    }
}
