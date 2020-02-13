<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Sync\DataExchange;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class SyncDataExchange implements SyncDataExchangeInterface
{
    /**
     * @var ReportBuilder
     */
    private $reportBuilder;

    /**
     * @var OrderExecutioner
     */
    private $orderExecutioner;

    /**
     * SyncDataExchange constructor.
     *
     * @param ReportBuilder    $reportBuilder
     * @param OrderExecutioner $orderExecutioner
     */
    public function __construct(ReportBuilder $reportBuilder, OrderExecutioner $orderExecutioner)
    {
        $this->reportBuilder    = $reportBuilder;
        $this->orderExecutioner = $orderExecutioner;
    }

    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
    {
        return $this->reportBuilder->build(
            $requestDAO->getSyncIteration(),
            $requestDAO->getObjects(),
            $requestDAO->getInputOptionsDAO()
        );
    }

    public function executeSyncOrder(OrderDAO $syncOrderDAO): void
    {
        $this->orderExecutioner->execute($syncOrderDAO);
    }
}
