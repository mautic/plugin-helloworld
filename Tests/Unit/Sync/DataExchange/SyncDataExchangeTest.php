<?php

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\DataExchange;

use MauticPlugin\HelloWorldBundle\Sync\DataExchange\OrderExecutioner;
use MauticPlugin\HelloWorldBundle\Sync\DataExchange\ReportBuilder;
use MauticPlugin\HelloWorldBundle\Sync\DataExchange\SyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;

class SyncDataExchangeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReportBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reportBuilder;

    /**
     * @var OrderExecutioner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderExecutioner;

    /**
     * @var SyncDataExchange
     */
    private $syncDataExchange;

    protected function setUp()
    {
        $this->reportBuilder    = $this->createMock(ReportBuilder::class);
        $this->orderExecutioner = $this->createMock(OrderExecutioner::class);
        $this->syncDataExchange = new SyncDataExchange($this->reportBuilder, $this->orderExecutioner);
    }

    public function testGetSyncReport()
    {
        $requestDAO = $this->createMock(RequestDAO::class);
        $requestDAO->expects($this->once())
            ->method('getSyncIteration')
            ->willReturn(1);

        $requestDAO->expects($this->once())
            ->method('getObjects')
            ->willReturn([]);

        $inputOptionsDAO = $this->createMock(InputOptionsDAO::class);
        $requestDAO->expects($this->once())
            ->method('getInputOptionsDAO')
            ->willReturn($inputOptionsDAO);

        $reportDAO = $this->createMock(ReportDAO::class);
        $this->reportBuilder->expects($this->once())
            ->method('build')
            ->with(1, [], $inputOptionsDAO)
            ->willReturn($reportDAO);

        $returnedReportDAO = $this->syncDataExchange->getSyncReport($requestDAO);

        $this->assertSame($reportDAO, $returnedReportDAO);
    }

    public function testExecuteSyncOrder()
    {
        $syncOrderDAO = $this->createMock(OrderDAO::class);

        $this->orderExecutioner->expects($this->once())
            ->method('execute')
            ->with($syncOrderDAO);

        $this->syncDataExchange->executeSyncOrder($syncOrderDAO);
    }
}
