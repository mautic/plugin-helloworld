<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Sync\DataExchange;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;

class OrderExecutioner
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var OrderDAO
     */
    private $order;

    /**
     * @var array<string,mixed[]>
     */
    private $mappedObjects = [
        MappingManualFactory::CITIZEN_OBJECT => [],
        MappingManualFactory::WORLD_OBJECT   => [],
    ];

    public function __construct(Client $client)
    {
        $this->client          = $client;
        $this->valueNormalizer = new ValueNormalizer();
    }

    public function execute(OrderDAO $orderDAO): void
    {
        $this->order = $orderDAO;

        // This integration supports two objects, citizen and world
        foreach ([MappingManualFactory::CITIZEN_OBJECT, MappingManualFactory::WORLD_OBJECT] as $objectName) {
            // Fetch the list of Mautic objects that have already been mapped to an object in the integration
            // and thus needs to be updated in the integration
            $identifiedObjects = $orderDAO->getIdentifiedObjects()[$objectName] ?? [];

            // Fetch the list of Mautic objects that have not been mapped to an object in the integration and
            // thus may need to be created or modified in the integration.
            $unidentifiedObjects = $orderDAO->getUnidentifiedObjects()[$objectName] ?? [];

            // Some integrations may require handling the two groups in different ways.
            // For the purpose of this example, it's assumed that the integration has a native upsert feature
            // Could also use $orderDAO->getChangedObjectsByObjectType($objectName) to get the same thing without
            // the merge.
            $changedObjects = array_merge($identifiedObjects, $unidentifiedObjects);

            // Not all integrations can be this easy. Some require more complicated processes such as checking if they
            // already exist in the integration before creating/updating and the like.
            if (!$changedObjects) {
                continue;
            }

            $this->upsertObjects($objectName, $changedObjects);
        }
    }

    /**
     * @param ObjectChangeDAO[] $changedObjects
     */
    private function upsertObjects(string $objectName, array $changedObjects): void
    {
        $data = [];
        foreach ($changedObjects as $objectChangeDAO) {
            $data[] = $this->prepareFieldPayload($objectChangeDAO);
        }

        $response = $this->client->upsert($objectName, $data);

        $this->processResponse($objectName, $response);
    }

    /**
     * @return array<string,mixed>
     */
    private function prepareFieldPayload(ObjectChangeDAO $objectChangeDAO): array
    {
        if ($id = $objectChangeDAO->getObjectId()) {
            // If the object is identified, just updated with the modified data
            $fields = $objectChangeDAO->getChangedFields();
            $datum  = ['id' => $id];
        } else {
            // Otherwise, merge required and changed fields to ensure a full profile.
            // This is simplified for the purposes of this example but may require more complex handling
            // for some integrations such as making API calls to determine if they already exist in the
            // integration
            $fields = array_merge($objectChangeDAO->getRequiredFields(), $objectChangeDAO->getChangedFields());
            $datum  = [];
        }

        // For the purpose of this example, it will be assumed that the integration's API accepts an identifier that is returned
        // in the response in order to identify which sub-response is associated with the payload.
        $datum['metadata'] = ['mautic_id' => $objectChangeDAO->getMappedObjectId()];

        // Store the object to the mapped Mautic ID for retrieval when processing the response
        $this->mappedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getMappedObjectId()] = $objectChangeDAO;

        /** @var FieldDAO $field */
        foreach ($fields as $field) {
            // Transform the data format from Mautic to what the integration expects
            $datum[$field->getName()] = $this->valueNormalizer->normalizeForIntegration(
                $field->getValue()
            );
        }

        return $datum;
    }

    /**
     * @param mixed[] $response
     */
    private function processResponse(string $objectName, array $response): void
    {
        foreach ($response as $itemResponse) {
            // Set the Mautic ID passed through back to us through the API to find the associated ObjectChangeDAO
            $mauticId        = $itemResponse['metadata']['mautic_id'];
            $objectChangeDAO = $this->mappedObjects[$objectName][$mauticId];

            // The order should be updated with the results of the sync by passing in the ObjectChangeDAO to appropriate method
            switch ($itemResponse['code']) {
                case 200:
                    // The object was updated so mark the last sync date
                    $this->order->updateLastSyncDate($objectChangeDAO);

                    break;
                case 201:
                    // The object was created so map the integration object to the Mautic object
                    $this->order->addObjectMapping(
                        $objectChangeDAO,
                        $objectChangeDAO->getObject(),
                        $itemResponse['id']
                    );

                    break;
                case 400:
                    // Validation failed or some other transient error. Note that this will not automatically retry the sync later
                    // unless $this->order->retrySyncLater() is used. See 503.
                    $this->order->noteObjectSyncIssue($objectChangeDAO, $itemResponse['message']);

                    break;
                case 404:
                    // It's assumed this means that the object no longer exists in the integration and thus mark it as deleted
                    // so that Mautic does not continue to attempt syncing.
                    $this->order->deleteObject($objectChangeDAO);

                    break;
                case 503:
                    // There was a temporary issue communicating with the server so retry this one again with the next sync.
                    $this->order->retrySyncLater($objectChangeDAO);

                    break;
            }

            // There is also the option to remap an object if for example it was converted from a Lead to a Contact
            // $this->order->remapObject($objectChangeDAO, $objectChangeDAO->getObjectId(), 'ANOTHER_OBJECT', $itemResponse['id']);
        }
    }
}
