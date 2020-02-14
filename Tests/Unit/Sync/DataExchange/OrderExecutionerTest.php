<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\DataExchange;

use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\DataExchange\OrderExecutioner;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;

class OrderExecutionerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    private $mauticObjects = [
        // Correlates with citizens_upsert.json and citizens_upsert_responses.json
        Contact::NAME => [
            10 => [
                'id'     => 1,
                'object' => MappingManualFactory::CITIZEN_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Alien1',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
            20 => [
                'id'     => 2,
                'object' => MappingManualFactory::CITIZEN_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Alien2',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
            30 => [
                'id'     => '',
                'object' => MappingManualFactory::CITIZEN_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Charlie',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'lastname',
                        'value' => 'Alien',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'email',
                        'value' => 'charlie@faraway.com',
                        'type'  => NormalizedValueDAO::EMAIL_TYPE,
                    ],
                    [
                        'name'  => 'opt_in',
                        'value' => 1,
                        'type'  => NormalizedValueDAO::BOOLEAN_TYPE,
                    ],
                ],
            ],
            40 => [
                'id'     => '',
                'object' => MappingManualFactory::CITIZEN_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Feliz',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'lastname',
                        'value' => 'Alien',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'email',
                        'value' => '',
                        'type'  => NormalizedValueDAO::EMAIL_TYPE,
                    ],
                    [
                        'name'  => 'opt_in',
                        'value' => 0,
                        'type'  => NormalizedValueDAO::BOOLEAN_TYPE,
                    ],
                ],
            ],
            50 => [
                'id'     => '',
                'object' => MappingManualFactory::CITIZEN_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Pancho',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'lastname',
                        'value' => 'Alien',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'email',
                        'value' => 'pancho@faraway.com',
                        'type'  => NormalizedValueDAO::EMAIL_TYPE,
                    ],
                    [
                        'name'  => 'opt_in',
                        'value' => 1,
                        'type'  => NormalizedValueDAO::BOOLEAN_TYPE,
                    ],
                ],
            ],
        ],
        // Correlates with worlds_upsert.json and worlds_upsert_responses.json
        Company::NAME => [
            10 => [
                'id'     => 1,
                'object' => MappingManualFactory::WORLD_OBJECT,
                'fields' => [
                    [
                        'name'  => 'type',
                        'value' => 'rock',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
            20 => [
                'id'     => '',
                'object' => MappingManualFactory::WORLD_OBJECT,
                'fields' => [
                    [
                        'name'  => 'name',
                        'value' => 'Saturn',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'type',
                        'value' => 'gas',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
    }

    public function testOrderIsExecuted(): void
    {
        $citizenPayload  = json_decode(file_get_contents(__DIR__.'/../../Connection/json/citizens_upsert.json'), true);
        $citizenResponse = json_decode(file_get_contents(__DIR__.'/../../Connection/json/citizens_upsert_response.json'), true);

        $worldPayload  = json_decode(file_get_contents(__DIR__.'/../../Connection/json/worlds_upsert.json'), true);
        $worldResponse = json_decode(file_get_contents(__DIR__.'/../../Connection/json/worlds_upsert_response.json'), true);

        $this->client->expects($this->exactly(2))
            ->method('upsert')
            ->withConsecutive(
                [MappingManualFactory::CITIZEN_OBJECT, $citizenPayload],
                [MappingManualFactory::WORLD_OBJECT, $worldPayload]
            )->willReturnOnConsecutiveCalls(
                $citizenResponse,
                $worldResponse
            );

        $order       = $this->createOrder();
        $executioner = new OrderExecutioner($this->client);

        $executioner->execute($order);

        $updated          = $order->getUpdatedObjectMappings();
        $expectedCitizens = [1];
        $foundCitizens    = [];
        $expectedWorlds   = [1];
        $foundWorlds      = [];
        foreach ($updated as $objectMappingDAO) {
            if (MappingManualFactory::CITIZEN_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundCitizens[] = $objectMappingDAO->getIntegrationObjectId();
            }

            if (MappingManualFactory::WORLD_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundWorlds[] = $objectMappingDAO->getIntegrationObjectId();
            }
        }
        $this->assertSame($expectedCitizens, $foundCitizens);
        $this->assertSame($expectedWorlds, $foundWorlds);

        $created          = $order->getObjectMappings();
        $expectedCitizens = [3];
        $foundCitizens    = [];
        $expectedWorlds   = [3];
        $foundWorlds      = [];
        foreach ($created as $objectMappingDAO) {
            if (MappingManualFactory::CITIZEN_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundCitizens[] = $objectMappingDAO->getIntegrationObjectId();
            }

            if (MappingManualFactory::WORLD_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundWorlds[] = $objectMappingDAO->getIntegrationObjectId();
            }
        }
        $this->assertSame($expectedCitizens, $foundCitizens);
        $this->assertSame($expectedWorlds, $foundWorlds);

        $deleted          = $order->getDeletedObjects();
        $expectedCitizens = [2];
        $foundCitizens    = [];
        $expectedWorlds   = [];
        $foundWorlds      = [];
        foreach ($deleted as $objectChangeDAO) {
            if (MappingManualFactory::CITIZEN_OBJECT === $objectChangeDAO->getObject()) {
                $foundCitizens[] = $objectChangeDAO->getObjectId();
            }

            if (MappingManualFactory::WORLD_OBJECT === $objectChangeDAO->getObject()) {
                $foundWorlds[] = $objectChangeDAO->getObjectId();
            }
        }
        $this->assertSame($expectedCitizens, $foundCitizens);
        $this->assertSame($expectedWorlds, $foundWorlds);

        $notifications     = $order->getNotifications();
        $expectedContacts  = [40 => 'Email is required'];
        $foundContacts     = [];
        $expectedCompanies = [];
        $foundCompanies    = [];
        foreach ($notifications as $notificationDAO) {
            if (MappingManualFactory::CITIZEN_OBJECT === $notificationDAO->getIntegrationObject()) {
                $foundContacts[$notificationDAO->getMauticObjectId()] = $notificationDAO->getMessage();
            }

            if (MappingManualFactory::WORLD_OBJECT === $notificationDAO->getIntegrationObject()) {
                $foundCompanies[$notificationDAO->getMauticObjectId()] = $notificationDAO->getMessage();
            }
        }
        $this->assertSame($expectedContacts, $foundContacts);
        $this->assertSame($expectedCompanies, $foundCompanies);

        // Retries should not be included in any of the above
    }

    private function createOrder(): OrderDAO
    {
        $order = new OrderDAO(new \DateTime(), false, HelloWorldIntegration::NAME);

        foreach ($this->mauticObjects as $mappedObjectName => $objects) {
            foreach ($objects as $mappedId => $object) {
                $objectChangeDAO = new ObjectChangeDAO(
                    HelloWorldIntegration::NAME,
                    $object['object'],
                    $object['id'],
                    $mappedObjectName,
                    $mappedId
                );

                foreach ($object['fields'] as $field) {
                    $objectChangeDAO->addField(
                        new FieldDAO($field['name'], new NormalizedValueDAO($field['type'], $field['value']))
                    );
                }

                $order->addObjectChange($objectChangeDAO);
            }
        }

        return $order;
    }
}
