<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Integration;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;

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

    protected function setUp(): void
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
    public function testIsPublished(): void
    {
        $this->integration->method('getIsPublished')
            ->willReturn(true);

        $this->assertTrue($this->config->isPublished());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getIntegrationEntity
     */
    public function testIsUnPublished(): void
    {
        $this->integration->method('getIsPublished')
            ->willReturn(false);

        $this->assertFalse($this->config->isPublished());
    }

    /**
     * @covers \MauticPlugin\HelloWorldBundle\Integration\Config::getApiKeys
     */
    public function testIsConfigured(): void
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
    public function testNotConfiguredIfClientIsMissing(): void
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
    public function testNotConfiguredIfSecretIsMissing(): void
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
    public function testIsAuthorized(): void
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
    public function testIsNotAuthorizedIfRefreshTokenIsMissing(): void
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

    public function testGetFeatureSettings(): void
    {
        $this->integration->method('getFeatureSettings')
            ->willReturn(
                [
                    'foo' => 'bar',
                ]
            );

        $this->assertEquals(['foo' => 'bar'], $this->config->getFeatureSettings());
    }

    public function testGetFieldDirection(): void
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

    public function testGetFieldThrowsInvalidValueExceptionIfFieldNotFound(): void
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

    public function testGetMappedFields(): void
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
