<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Connection;

use MauticPlugin\HelloWorldBundle\Connection\Credentials;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters(): void
    {
        $clientId     = 'foo';
        $clientSecret = 'bar';

        $credentials = new Credentials($clientId, $clientSecret);

        $this->assertEquals($clientId, $credentials->getClientId());
        $this->assertEquals($clientSecret, $credentials->getClientSecret());
        $this->assertEquals('https://hello.world/authorize', $credentials->getAuthorizationUrl());
    }
}
