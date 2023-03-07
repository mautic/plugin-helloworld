<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;

class Config
{
    private IntegrationsHelper $integrationsHelper;

    /**
     * @var array<string,mixed[]>
     */
    private array $fieldDirections = [];

    /**
     * @var array<string,mixed[]>
     */
    private $mappedFields = [];

    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    public function isPublished(): bool
    {
        try {
            $integration = $this->getIntegrationEntity();

            return (bool) $integration->getIsPublished() ?: false;
        } catch (IntegrationNotFoundException $e) {
            return false;
        }
    }

    public function isConfigured(): bool
    {
        $apiKeys = $this->getApiKeys();

        return !empty($apiKeys['client_id']) && !empty($apiKeys['client_secret']);
    }

    public function isAuthorized(): bool
    {
        $apiKeys = $this->getApiKeys();

        return !empty($apiKeys['refresh_token']);
    }

    /**
     * @return mixed[]
     */
    public function getFeatureSettings(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getFeatureSettings() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }

    /**
     * @return string[]
     */
    public function getApiKeys(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getApiKeys() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }

    /**
     * @throws InvalidValueException
     */
    public function getFieldDirection(string $objectName, string $alias): string
    {
        if (isset($this->getMappedFieldsDirections($objectName)[$alias])) {
            return $this->getMappedFieldsDirections($objectName)[$alias];
        }

        throw new InvalidValueException("There is no field direction for '{$objectName}' field '${alias}'.");
    }

    /**
     * Returns mapped fields that the user configured for this integration in the format of [field_alias => mautic_field_alias].
     *
     * @return string[]
     */
    public function getMappedFields(string $objectName): array
    {
        if (isset($this->mappedFields[$objectName])) {
            return $this->mappedFields[$objectName];
        }

        $fieldMappings = $this->getFeatureSettings()['sync']['fieldMappings'][$objectName] ?? [];

        $this->mappedFields[$objectName] = [];
        foreach ($fieldMappings as $field => $fieldMapping) {
            $this->mappedFields[$objectName][$field] = $fieldMapping['mappedField'];
        }

        return $this->mappedFields[$objectName];
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(HelloWorldIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }

    /**
     * Returns direction of what field to sync where in the format of [field_alias => direction].
     *
     * @return string[]
     */
    private function getMappedFieldsDirections(string $objectName): array
    {
        if (isset($this->fieldDirections[$objectName])) {
            return $this->fieldDirections[$objectName];
        }

        $fieldMappings = $this->getFeatureSettings()['sync']['fieldMappings'][$objectName] ?? [];

        $this->fieldDirections[$objectName] = [];
        foreach ($fieldMappings as $field => $fieldMapping) {
            $this->fieldDirections[$objectName][$field] = $fieldMapping['syncDirection'];
        }

        return $this->fieldDirections[$objectName];
    }
}
