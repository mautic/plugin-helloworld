<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\Mapping\Manual;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;

class MappingManualFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    private FieldRepository $fieldRepository;

    /**
     * @var CacheStorageHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheStorageProvider;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    private MappingManualFactory $mappingManualFactory;

    protected function setUp(): void
    {
        $this->cacheStorageProvider = $this->createMock(CacheStorageHelper::class);
        $this->client               = $this->createMock(Client::class);
        $this->fieldRepository      = new FieldRepository($this->cacheStorageProvider, $this->client);
        $this->config               = $this->createMock(Config::class);
        $this->mappingManualFactory = new MappingManualFactory($this->fieldRepository, $this->config);
    }

    public function testMappingManualIsCompiledAndReturned(): void
    {
        $citizenFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/citizens_fields.json'), true);
        $worldFields   = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/worlds_fields.json'), true);

        $this->cacheStorageProvider->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['helloworld.fields.'.MappingManualFactory::CITIZEN_OBJECT],
                ['helloworld.fields.'.MappingManualFactory::WORLD_OBJECT]
            )->willReturnOnConsecutiveCalls(
                $citizenFields,
                $worldFields
            );

        $this->config->expects($this->exactly(2))
            ->method('getMappedFields')
            ->withConsecutive(
                [MappingManualFactory::CITIZEN_OBJECT],
                [MappingManualFactory::WORLD_OBJECT]
            )->willReturnOnConsecutiveCalls(
                [
                    'firstname' => 'first_name',
                    'lastname'  => 'last_name',
                    'email'     => 'email',
                    'opt_in'    => 'opt_in',
                ],
                [
                    'name' => 'world_name',
                    'type' => 'world_type',
                ]
            );

        $this->config->expects($this->exactly(6))
            ->method('getFieldDirection')
            ->withConsecutive(
                [MappingManualFactory::CITIZEN_OBJECT, 'firstname'],
                [MappingManualFactory::CITIZEN_OBJECT, 'lastname'],
                [MappingManualFactory::CITIZEN_OBJECT, 'email'],
                [MappingManualFactory::CITIZEN_OBJECT, 'opt_in'],
                [MappingManualFactory::WORLD_OBJECT, 'name'],
                [MappingManualFactory::WORLD_OBJECT, 'type']
            )->willReturnOnConsecutiveCalls(
                ObjectMappingDAO::SYNC_TO_MAUTIC,
                ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ObjectMappingDAO::SYNC_TO_MAUTIC,
                ObjectMappingDAO::SYNC_TO_INTEGRATION,
                ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ObjectMappingDAO::SYNC_TO_MAUTIC
            );

        $manual = $this->mappingManualFactory->getManual();

        // bidirectional and sync to mautic fields should be included
        $syncToMautic = $manual->getIntegrationObjectFieldsToSyncToMautic(MappingManualFactory::CITIZEN_OBJECT);
        $this->assertTrue(in_array('firstname', $syncToMautic));
        $this->assertTrue(in_array('lastname', $syncToMautic));
        $this->assertTrue(in_array('email', $syncToMautic));
        $this->assertFalse(in_array('opt_in', $syncToMautic));

        // bidirectional and sync to integration should be in array
        $syncToIntegration = $manual->getInternalObjectFieldsToSyncToIntegration(Contact::NAME);
        $this->assertFalse(in_array('first_name', $syncToIntegration));
        $this->assertTrue(in_array('last_name', $syncToIntegration));
        // Email is included because it's required even though it is set to SYNC_TO_MAUTIC
        $this->assertTrue(in_array('email', $syncToIntegration));
        $this->assertTrue(in_array('opt_in', $syncToIntegration));
    }
}
