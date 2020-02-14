<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\DataExchange;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\DataExchange\ReportBuilder;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;

class ReportBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var CacheStorageHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheStorageProvider;

    /**
     * @var ReportBuilder
     */
    private $reportBuilder;

    protected function setUp(): void
    {
        $this->client               = $this->createMock(Client::class);
        $this->config               = $this->createMock(Config::class);
        $this->cacheStorageProvider = $this->createMock(CacheStorageHelper::class);
        $this->fieldRepository      = new FieldRepository($this->cacheStorageProvider, $this->client);
        $this->reportBuilder        = new ReportBuilder($this->client, $this->config, $this->fieldRepository);
    }

    public function testReportIsBuilt(): void
    {
        $citizenFields = json_decode(file_get_contents(__DIR__.'/../../Connection/json/citizens_fields.json'), true);
        $worldFields   = json_decode(file_get_contents(__DIR__.'/../../Connection/json/worlds_fields.json'), true);

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

        $page             = 1;
        $options          = new InputOptionsDAO(
            [
                'integration'     => HelloWorldIntegration::NAME,
                'first-time-sync' => false,
                'start-datetime'  => '2020-02-01 00:00:00',
                'end-datetime'    => '2020-02-13 00:00:00',
            ]
        );
        $requestedObjects = [
            new ObjectDAO(MappingManualFactory::CITIZEN_OBJECT, $options->getStartDateTime(), $options->getEndDateTime()),
            new ObjectDAO(MappingManualFactory::WORLD_OBJECT, $options->getStartDateTime(), $options->getEndDateTime()),
        ];

        $citizenResponse = json_decode(file_get_contents(__DIR__.'/../../Connection/json/citizens.json'), true);
        $worldResponse   = json_decode(file_get_contents(__DIR__.'/../../Connection/json/worlds.json'), true);

        $this->client->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [MappingManualFactory::CITIZEN_OBJECT, $options->getStartDateTime(), $options->getEndDateTime(), $page],
                [MappingManualFactory::WORLD_OBJECT, $options->getStartDateTime(), $options->getEndDateTime(), $page]
            )->willReturnOnConsecutiveCalls(
                $citizenResponse,
                $worldResponse
            );

        $report = $this->reportBuilder->build($page, $requestedObjects, $options);

        $citizens = $report->getObjects(MappingManualFactory::CITIZEN_OBJECT);

        // Citizen 1 has been updated
        $this->assertTrue(isset($citizens[1]));
        $this->assertEquals($citizenResponse[0]['fields']['firstname'], $citizens[1]->getField('firstname')->getValue()->getNormalizedValue());
        $this->assertEquals($citizenResponse[0]['fields']['lastname'], $citizens[1]->getField('lastname')->getValue()->getNormalizedValue());
        $this->assertEquals($citizenResponse[0]['fields']['email'], $citizens[1]->getField('email')->getValue()->getNormalizedValue());
        $this->assertEquals((int) $citizenResponse[0]['fields']['opt_in'], $citizens[1]->getField('opt_in')->getValue()->getNormalizedValue());
        $this->assertEquals($citizenResponse[0]['last_modified_timestamp'], $citizens[1]->getChangeDateTime()->format('Y-m-d H:i:s'));
        // home_planet was not mapped and thus should not be included
        $this->assertTrue(empty($citizens[1]->getFields()['home_planet']));

        // Citizen 2 is new
        $this->assertTrue(isset($citizens[2]));
        $this->assertEquals($citizenResponse[1]['fields']['firstname'], $citizens[2]->getField('firstname')->getValue()->getNormalizedValue());
        $this->assertEquals($citizenResponse[1]['fields']['lastname'], $citizens[2]->getField('lastname')->getValue()->getNormalizedValue());
        $this->assertEquals($citizenResponse[1]['fields']['email'], $citizens[2]->getField('email')->getValue()->getNormalizedValue());
        $this->assertEquals((int) $citizenResponse[1]['fields']['opt_in'], $citizens[2]->getField('opt_in')->getValue()->getNormalizedValue());
        $this->assertEquals($citizenResponse[1]['created_timestamp'], $citizens[2]->getChangeDateTime()->format('Y-m-d H:i:s'));

        $worlds = $report->getObjects(MappingManualFactory::WORLD_OBJECT);

        // World 1 has been updated
        $this->assertTrue(isset($worlds[1]));
        $this->assertEquals($worldResponse[0]['fields']['name'], $worlds[1]->getField('name')->getValue()->getNormalizedValue());
        $this->assertEquals($worldResponse[0]['fields']['type'], $worlds[1]->getField('type')->getValue()->getNormalizedValue());
        $this->assertEquals($worldResponse[0]['last_modified_timestamp'], $worlds[1]->getChangeDateTime()->format('Y-m-d H:i:s'));

        // World 2 is new
        $this->assertTrue(isset($worlds[2]));
        $this->assertEquals($worldResponse[1]['fields']['name'], $worlds[2]->getField('name')->getValue()->getNormalizedValue());
        $this->assertEquals($worldResponse[1]['fields']['type'], $worlds[2]->getField('type')->getValue()->getNormalizedValue());
        $this->assertEquals($worldResponse[1]['created_timestamp'], $worlds[2]->getChangeDateTime()->format('Y-m-d H:i:s'));
    }
}
