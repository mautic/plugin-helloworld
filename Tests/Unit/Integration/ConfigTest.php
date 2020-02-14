<?php

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Integration;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;
use MauticPlugin\IntegrationsBundle\Exception\InvalidValueException;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Integration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integration;

    /**
     * @var IntegrationsHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationsHelper;

    /**
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->integration        = $this->createMock(Integration::class);
        $integrationInterface     = $this->createMock(IntegrationInterface::class);
        $integrationInterface->method('getIntegrationConfiguration')
            ->willReturn($this->integration);

        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $this->integrationsHelper->method('getIntegration')
            ->with(HelloWorldIntegration::NAME)
            ->willReturn($integrationInterface);

        $this->config = new Config($this->integrationsHelper);
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getIntegrationEntity
     */
    public function testIsPublished()
    {
        $this->integration->method('getIsPublished')
            ->willReturn(true);

        $this->assertTrue($this->config->isPublished());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getIntegrationEntity
     */
    public function testIsUnPublished()
    {
        $this->integration->method('getIsPublished')
            ->willReturn(false);

        $this->assertFalse($this->config->isPublished());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getApiKeys
     */
    public function testIsConfigured()
    {
        $this->integration->method('getApiKeys')
            ->willReturn(
                [
                    'client_id'     => 'foo',
                    'client_secret' => 'bar',
                ]
            );

        $this->assertTrue($this->config->isConfigured());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getApiKeys
     */
    public function testNotConfiguredIfClientIsMissing()
    {
        $this->integration->method('getApiKeys')
            ->willReturn(
                [
                    'client_id'     => '',
                    'client_secret' => 'bar',
                ]
            );

        $this->assertFalse($this->config->isConfigured());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getApiKeys
     */
    public function testNotConfiguredIfSecretIsMissing()
    {
        $this->integration->method('getApiKeys')
            ->willReturn(
                [
                    'client_id'     => 'foo',
                    'client_secret' => '',
                ]
            );

        $this->assertFalse($this->config->isConfigured());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getApiKeys
     */
    public function testIsAuthorized()
    {
        $this->integration->method('getApiKeys')
            ->willReturn(
                [
                    'client_id'     => 'foo',
                    'client_secret' => 'bar',
                    'refresh_token' => 'abc123',
                ]
            );

        $this->assertTrue($this->config->isConfigured());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getApiKeys
     */
    public function testIsNotAuthorizedIfRefreshTokenIsMissing()
    {
        $this->integration->method('getApiKeys')
            ->willReturn(
                [
                    'client_id'     => 'foo',
                    'client_secret' => 'bar',
                    'refresh_token' => '',
                ]
            );

        $this->assertFalse($this->config->isAuthorized());
    }

    public function testGetFeatureSettings()
    {
        $this->integration->method('getFeatureSettings')
            ->willReturn(
                [
                    'foo' => 'bar',
                ]
            );

        $this->assertEquals(['foo' => 'bar'], $this->config->getFeatureSettings());
    }

    public function testGetFieldDirection()
    {
        $this->integration->method('getFeatureSettings')
            ->willReturn(
                [
                    'sync' => [
                        'fieldMappings' => [
                            MappingManualFactory::CITIZEN_OBJECT => [
                                'firstname' => [
                                    'syncDirection' => ObjectMappingDAO::SYNC_TO_MAUTIC,
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $this->config->getFieldDirection(MappingManualFactory::CITIZEN_OBJECT, 'firstname'));
    }

    public function testGetFieldThrowsInvalidValueExceptionIfFieldNotFound()
    {
        $this->expectException(InvalidValueException::class);
        $this->integration->method('getFeatureSettings')
            ->willReturn(
                [
                    'sync' => [
                        'fieldMappings' => [
                            MappingManualFactory::CITIZEN_OBJECT => [
                                'firstname' => [
                                    'syncDirection' => ObjectMappingDAO::SYNC_TO_MAUTIC,
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $this->config->getFieldDirection(MappingManualFactory::CITIZEN_OBJECT, 'lastname'));
    }

    public function testGetMappedFields()
    {
        $this->integration->method('getFeatureSettings')
            ->willReturn(
                [
                    'sync' => [
                        'fieldMappings' => [
                            MappingManualFactory::CITIZEN_OBJECT => [
                                'firstname' => [
                                    'syncDirection' => ObjectMappingDAO::SYNC_TO_MAUTIC,
                                    'mappedField'   => 'first_name',
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->assertEquals(['firstname' => 'first_name'], $this->config->getMappedFields(MappingManualFactory::CITIZEN_OBJECT));
    }
}
