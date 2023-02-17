<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Sync\DataExchange;

use GuzzleHttp\Exception\GuzzleException;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\HelloWorldBundle\Connection\Client;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\Field;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;

class ReportBuilder
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var ReportDAO
     */
    private $report;

    public function __construct(Client $client, Config $config, FieldRepository $fieldRepository)
    {
        $this->client          = $client;
        $this->config          = $config;
        $this->fieldRepository = $fieldRepository;

        // Value normalizer transforms value types expected by each side of the sync
        $this->valueNormalizer = new ValueNormalizer();
    }

    /**
     * @param RequestObjectDAO[] $requestedObjects
     *
     * @throws GuzzleException
     * @throws IntegrationNotFoundException
     * @throws InvalidCredentialsException
     * @throws PluginNotConfiguredException
     */
    public function build(int $page, array $requestedObjects, InputOptionsDAO $options): ReportDAO
    {
        // Set the options this integration supports (see InputOptionsDAO for others)
        $startDateTime = $options->getStartDateTime();
        $endDateTime   = $options->getEndDateTime();

        $this->report = new ReportDAO(HelloWorldIntegration::NAME);

        foreach ($requestedObjects as $requestedObject) {
            $objectName = $requestedObject->getObject();
            // Fetch a list of changed objects from the integration's API
            $modifiedItems = $this->client->get(
                $objectName,
                $startDateTime,
                $endDateTime,
                $page
            );

            // Add the modified items to the report
            $this->addModifiedItems($objectName, $modifiedItems);
        }

        return $this->report;
    }

    /**
     * @param mixed[] $changeList
     */
    private function addModifiedItems(string $objectName, array $changeList): void
    {
        // Get the the field list to know what the field types are
        $fields       = $this->fieldRepository->getFields($objectName);
        $mappedFields = $this->config->getMappedFields($objectName);

        foreach ($changeList as $item) {
            $objectDAO = new ReportObjectDAO(
                $objectName,
                // Set the ID from the integration
                $item['id'],
                // Set the date/time when the full object was last modified or created
                new \DateTime(!empty($item['last_modified_timestamp']) ? $item['last_modified_timestamp'] : $item['created_timestamp'])
            );

            foreach ($item['fields'] as $fieldAlias => $fieldValue) {
                if (!isset($fields[$fieldAlias]) || !isset($mappedFields[$fieldAlias])) {
                    // Field is not recognized or it's not mapped so ignore
                    continue;
                }

                /** @var Field $field */
                $field = $fields[$fieldAlias];

                // The sync is currently from Integration to Mautic so normalize the values for storage in Mautic
                $normalizedValue = $this->valueNormalizer->normalizeForMautic(
                    $fieldValue,
                    $field->getDataType()
                );

                // If the integration supports field level tracking with timestamps, update FieldDAO::setChangeDateTime as well
                // Note that the field name here is the integration's
                $objectDAO->addField(new FieldDAO($fieldAlias, $normalizedValue));
            }

            // Add the modified/new item to the report
            $this->report->addObject($objectDAO);
        }
    }
}
