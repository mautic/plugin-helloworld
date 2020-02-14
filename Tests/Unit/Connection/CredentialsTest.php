<?php

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Connection;

use MauticPlugin\HelloWorldBundle\Connection\Credentials;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $clientId     = 'foo';
        $clientSecret = 'bar';

        $credentials = new Credentials($clientId, $clientSecret);

        $this->assertEquals($clientId, $credentials->getClientId());
        $this->assertEquals($clientSecret, $credentials->getClientSecret());
        $this->assertEquals('https://hello.world/authorize', $credentials->getAuthorizationUrl());
    }
}
