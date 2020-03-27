<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Integration\Support;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthorizeButtonInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormCallbackInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\HelloWorldBundle\Form\Type\ConfigAuthType;
use MauticPlugin\HelloWorldBundle\Form\Type\ConfigFeaturesType;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class ConfigSupport extends HelloWorldIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeatureSettingsInterface, ConfigFormSyncInterface, ConfigFormFeaturesInterface, ConfigFormAuthorizeButtonInterface, ConfigFormCallbackInterface
{
    use DefaultConfigFormTrait;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(FieldRepository $fieldRepository, Config $config, Session $session, Router $router, TranslatorInterface $translator)
    {
        $this->fieldRepository = $fieldRepository;
        $this->session         = $session;
        $this->router          = $router;
        $this->config          = $config;
        $this->translator      = $translator;
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

    public function isAuthorized(): bool
    {
        return $this->config->isAuthorized();
    }

    public function getAuthorizationUrl(): string
    {
        // Generate and set the state in the session so that it can be validated when the authorization process redirects to the redirect URL
        $state = EncryptionHelper::generateKey();
        $this->session->set('helloworld.state', $state);

        $params = [
            'client_id'     => $this->getIntegrationConfiguration()->getApiKeys()['client_id'] ?? '',
            'response_type' => 'code',
            'redirect_uri'  => $this->getRedirectUri(),
            'scope'         => 'api refresh_token',
            'state'         => $state,
        ];

        return $this->router->generate(
            'helloworld_mocked_authorization_endpoint',
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getCallbackHelpMessageTranslationKey(): string
    {
        if ($this->isAuthorized()) {
            return $this->translator->trans('helloworld.auth.is_authorized', ['%access_token%' => $this->config->getApiKeys()['access_token']]);
        }

        return $this->translator->trans('helloworld.auth.is_not_authorized');
    }

    public function getRedirectUri(): string
    {
        return $this->router->generate(
            'mautic_integration_public_callback',
            ['integration' => HelloWorldIntegration::NAME],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
