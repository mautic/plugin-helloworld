<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Integration\Support;

use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use MauticPlugin\HelloWorldBundle\Sync\Mapping\Manual\MappingManualFactory;
use Mautic\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class SyncSupport extends HelloWorldIntegration implements SyncInterface
{
    /**
     * @var MappingManualFactory
     */
    private $mappingManualFactory;

    /**
     * @var SyncDataExchangeInterface
     */
    private $syncDataExchange;

    public function __construct(MappingManualFactory $mappingManualFactory, SyncDataExchangeInterface $syncDataExchange)
    {
        $this->mappingManualFactory = $mappingManualFactory;
        $this->syncDataExchange     = $syncDataExchange;
    }

    public function getSyncDataExchange(): SyncDataExchangeInterface
    {
        return $this->syncDataExchange;
    }

    public function getMappingManual(): MappingManualDAO
    {
        return $this->mappingManualFactory->getManual();
    }
}
