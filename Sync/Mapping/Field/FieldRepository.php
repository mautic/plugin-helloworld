<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Sync\Mapping\Field;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use MauticPlugin\HelloWorldBundle\Connection\Client;

class FieldRepository
{
    /**
     * @var CacheStorageHelper
     */
    private $cacheProvider;

    /**
     * @var Client
     */
    private $client;

    public function __construct(CacheStorageHelper $cacheProvider, Client $client)
    {
        $this->cacheProvider = $cacheProvider;
        $this->client        = $client;
    }

    /**
     * Used by the sync engine so that it does not have to fetch the fields live with each object sync.
     *
     * @return Field[]
     */
    public function getFieldsFromCache(string $object): array
    {
        $cacheKey = $this->getCacheKey($object);
        $fields   = $this->cacheProvider->get($cacheKey);

        if (!$fields) {
            // Fields are empty or not found so refresh from the API
            $fields = $this->getFieldsFromApi($object);

            // Refresh the cache with the fields just fetched
            $this->cacheProvider->set($cacheKey, $fields);
        }

        return $this->hydrateFieldObjects($fields);
    }

    /**
     * Fetch the fields fresh from the API.
     */
    public function getFieldsFromApi(string $object): array
    {
        $fields = $this->client->getFields($object);

        return $this->hydrateFieldObjects($fields);
    }

    private function getCacheKey(string $object): string
    {
        return sprintf('helloworld.fields.%s', $object);
    }

    /**
     * @param array $fields
     *
     * @return Field[]
     */
    private function hydrateFieldObjects(array $fields): array
    {
        $fieldObjects = [];
        foreach ($fields as $field) {
            $fieldObjects[$field['name']] = new Field($field);
        }

        return $fieldObjects;
    }
}
