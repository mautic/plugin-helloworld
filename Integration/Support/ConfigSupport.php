<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\HelloWorldBundle\Form\Type\ConfigAuthType;
use MauticPlugin\HelloWorldBundle\Form\Type\ConfigFeaturesType;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;

class ConfigSupport extends HelloWorldIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeatureSettingsInterface, ConfigFormSyncInterface, ConfigFormFeaturesInterface
{
    use DefaultConfigFormTrait;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * ConfigSupport constructor.
     *
     * @param FieldRepository $fieldRepository
     */
    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }

    public function getFeatureSettingsConfigFormName(): string
    {
        return ConfigFeaturesType::class;
    }

    public function getSyncConfigObjects(): array
    {
        return [
            MappingManualFactory::CITIZEN_OBJECT => 'helloworld.object.citizen',
            MappingManualFactory::WORLD_OBJECT   => 'helloworld.object.world',
        ];
    }

    public function getSyncMappedObjects(): array
    {
        return [
            MappingManualFactory::CITIZEN_OBJECT => Contact::NAME,
            MappingManualFactory::WORLD_OBJECT   => Company::NAME,
        ];
    }

    /**
     * @return MappedFieldInfoInterface[]
     */
    public function getRequiredFieldsForMapping(string $objectName): array
    {
        return $this->fieldRepository->getRequiredFieldsForMapping($objectName);
    }

    /**
     * @return MappedFieldInfoInterface[]
     */
    public function getOptionalFieldsForMapping(string $objectName): array
    {
        $this->fieldRepository->getOptionalFieldsForMapping($objectName);
    }

    /**
     * @return MappedFieldInfoInterface[]
     */
    public function getAllFieldsForMapping(string $objectName): array
    {
        // Order fields by required alphabetical then optional alphabetical
        $sorter = function (MappedFieldInfoInterface $field1, MappedFieldInfoInterface $field2) {
            return strnatcasecmp($field1->getLabel(), $field2->getLabel());
        };

        $requiredFields = $this->fieldRepository->getRequiredFieldsForMapping($objectName);
        uasort($requiredFields, $sorter);

        $optionalFields = $this->fieldRepository->getOptionalFieldsForMapping($objectName);
        uasort($optionalFields, $sorter);

        return array_merge(
            $requiredFields,
            $optionalFields
        );
    }

    public function getSupportedFeatures(): array
    {
        return [
            ConfigFormFeaturesInterface::FEATURE_SYNC => 'mautic.integration.feature.sync',
        ];
    }
}
