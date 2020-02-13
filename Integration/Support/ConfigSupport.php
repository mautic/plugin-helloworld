<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Integration\Support;


use MauticPlugin\HelloWorldBundle\Form\Type\ConfigAuthType;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;

class ConfigSupport extends HelloWorldIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    /**
     * Return a custom Symfony form field type class that will be used on the Enabled/Auth tab.
     * This should include things like API credentials, URLs, etc. All values from this form fields
     * will be encrypted before being persisted.
     *
     * @link https://symfony.com/doc/2.8/form/create_custom_field_type.html#defining-the-field-type
     *
     * @return string
     */
    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }
}