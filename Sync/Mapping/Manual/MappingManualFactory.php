<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual;

use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\Field;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;

class MappingManualFactory
{
    public const CITIZEN_OBJECT = 'citizen';
    public const WORLD_OBJECT   = 'world';

    private FieldRepository $fieldRepository;
    private Config $config;
    private ?MappingManualDAO $manual = null;

    public function __construct(FieldRepository $fieldRepository, Config $config)
    {
        $this->fieldRepository = $fieldRepository;
        $this->config          = $config;
    }

    public function getManual(): MappingManualDAO
    {
        if ($this->manual) {
            return $this->manual;
        }

        // Instructions to the sync engine on how to map fields and the direction of data should flow
        $this->manual = new MappingManualDAO(HelloWorldIntegration::NAME);

        // In this case, two objects are supported. Citizen to Mautic Contact and World to Mautic Company.
        $this->configureObjectMapping(self::CITIZEN_OBJECT);
        $this->configureObjectMapping(self::WORLD_OBJECT);

        return $this->manual;
    }

    private function configureObjectMapping(string $objectName): void
    {
        // Get a list of available fields from the integration
        $fields = $this->fieldRepository->getFields($objectName);

        // Get a list of fields mapped by the user
        $mappedFields = $this->config->getMappedFields($objectName);

        // Generate an object mapping DAO for the given object. The object must be mapped to a supported Mautic object (i.e. contact or company)
        $objectMappingDAO = new ObjectMappingDAO($this->getMauticObjectName($objectName), $objectName);

        foreach ($mappedFields as $fieldAlias => $mauticFieldAlias) {
            if (!isset($fields[$fieldAlias])) {
                // The mapped field is no longer available
                continue;
            }

            /** @var Field $field */
            $field = $fields[$fieldAlias];

            // Configure how fields should be handled by the sync engine as determined by the user's configuration.
            $objectMappingDAO->addFieldMapping(
                $mauticFieldAlias,
                $fieldAlias,
                $this->config->getFieldDirection($objectName, $fieldAlias),
                $field->isRequired()
            );

            $this->manual->addObjectMapping($objectMappingDAO);
        }
    }

    /**
     * @throws InvalidValueException
     */
    private function getMauticObjectName(string $objectName): string
    {
        switch ($objectName) {
            case self::WORLD_OBJECT:
                return Company::NAME;
            case self::CITIZEN_OBJECT:
                return Contact::NAME;
        }

        throw new InvalidValueException("$objectName could not be mapped to a Mautic object");
    }
}
