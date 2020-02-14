<?php

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\Mapping\Field;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\Field;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;

class FieldRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var CacheStorageHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheStorageProvider;

    protected function setUp()
    {
        $this->client               = $this->createMock(Client::class);
        $this->cacheStorageProvider = $this->createMock(CacheStorageHelper::class);
        $this->fieldRepository      = new FieldRepository($this->cacheStorageProvider, $this->client);
    }

    public function testFieldsAreFetchedFromCache()
    {
        $citizenFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/citizens_fields.json'), true);

        $this->cacheStorageProvider->expects($this->once())
            ->method('get')
            ->with('helloworld.fields.'.MappingManualFactory::CITIZEN_OBJECT)
            ->willReturn($citizenFields);

        $fields = $this->fieldRepository->getFieldsFromCache(MappingManualFactory::CITIZEN_OBJECT);
        $this->assertCount(6, $fields);

        $this->assertInstanceOf(Field::class, $fields['id']);
    }

    public function testFieldsAreFetchedLiveIfCacheIsNotAvailable()
    {
        $citizenFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/citizens_fields.json'), true);

        $this->cacheStorageProvider->expects($this->once())
            ->method('get')
            ->with('helloworld.fields.'.MappingManualFactory::CITIZEN_OBJECT)
            ->willReturn([]);

        $this->client->expects($this->once())
            ->method('getFields')
            ->with(MappingManualFactory::CITIZEN_OBJECT)
            ->willReturn($citizenFields);

        $fields = $this->fieldRepository->getFieldsFromCache(MappingManualFactory::CITIZEN_OBJECT);
        $this->assertCount(6, $fields);

        $this->assertInstanceOf(Field::class, $fields['id']);
    }

    public function testFieldsAreFetchedFromApi()
    {
        $citizenFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/citizens_fields.json'), true);

        $this->cacheStorageProvider->expects($this->never())
            ->method('get');

        $this->client->expects($this->once())
            ->method('getFields')
            ->with(MappingManualFactory::CITIZEN_OBJECT)
            ->willReturn($citizenFields);

        $fields = $this->fieldRepository->getFieldsFromApi(MappingManualFactory::CITIZEN_OBJECT);
        $this->assertCount(6, $fields);

        $this->assertInstanceOf(Field::class, $fields['id']);
    }
}
