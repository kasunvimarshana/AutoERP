<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Sales;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\CreateStockMovementServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesDocumentLinkRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesPaymentAllocationRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesSettingRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesStatusHistoryRepositoryInterface;
use Modules\Sales\Application\Services\SalesWorkflowService;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SalesWorkflowServiceTest extends TestCase
{
    private SalesOrderRepositoryInterface&MockObject $salesOrders;

    private SalesOrderLineRepositoryInterface&MockObject $salesOrderLines;

    private GdnHeaderRepositoryInterface&MockObject $gdns;

    private GdnLineRepositoryInterface&MockObject $gdnLines;

    private SalesReturnRepositoryInterface&MockObject $salesReturns;

    private SalesReturnLineRepositoryInterface&MockObject $salesReturnLines;

    private SalesSettingRepositoryInterface&MockObject $settings;

    private SalesStatusHistoryRepositoryInterface&MockObject $history;

    private CreateStockMovementServiceInterface&MockObject $stockMovements;

    private FinancePostingServiceInterface&MockObject $financePosting;

    private ItemRepositoryInterface&MockObject $items;

    private UnitOfMeasureRepositoryInterface&MockObject $uoms;

    private UomConversionServiceInterface&MockObject $uomConversion;

    private SalesWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salesOrders = $this->createMock(SalesOrderRepositoryInterface::class);
        $this->salesOrderLines = $this->createMock(SalesOrderLineRepositoryInterface::class);
        $this->gdns = $this->createMock(GdnHeaderRepositoryInterface::class);
        $this->gdnLines = $this->createMock(GdnLineRepositoryInterface::class);
        $this->salesReturns = $this->createMock(SalesReturnRepositoryInterface::class);
        $this->salesReturnLines = $this->createMock(SalesReturnLineRepositoryInterface::class);
        $documentLinks = $this->createMock(SalesDocumentLinkRepositoryInterface::class);
        $paymentAllocations = $this->createMock(SalesPaymentAllocationRepositoryInterface::class);
        $this->settings = $this->createMock(SalesSettingRepositoryInterface::class);
        $this->history = $this->createMock(SalesStatusHistoryRepositoryInterface::class);
        $documentOrchestrator = $this->createMock(DocumentOrchestrator::class);
        $paymentAllocationService = $this->createMock(PaymentAllocationServiceInterface::class);
        $advanceAllocationService = $this->createMock(AdvancePaymentAllocationServiceInterface::class);
        $this->stockMovements = $this->createMock(CreateStockMovementServiceInterface::class);
        $this->financePosting = $this->createMock(FinancePostingServiceInterface::class);
        $priceResolver = $this->createMock(PriceResolverServiceInterface::class);
        $this->items = $this->createMock(ItemRepositoryInterface::class);
        $this->uoms = $this->createMock(UnitOfMeasureRepositoryInterface::class);
        $this->uomConversion = $this->createMock(UomConversionServiceInterface::class);

        $this->salesOrders->method('transaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $this->gdns->method('transaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $this->salesReturns->method('transaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $this->history->method('create')->willReturn(new DataRecord(['id' => 1]));

        $this->service = new SalesWorkflowService(
            $this->salesOrders,
            $this->salesOrderLines,
            $this->gdns,
            $this->gdnLines,
            $this->salesReturns,
            $this->salesReturnLines,
            $documentLinks,
            $paymentAllocations,
            $this->settings,
            $this->history,
            $documentOrchestrator,
            $paymentAllocationService,
            $advanceAllocationService,
            $this->stockMovements,
            $this->financePosting,
            $priceResolver,
            $this->items,
            $this->uoms,
            $this->uomConversion,
        );
    }

    public function test_preview_finance_builds_balanced_stockable_sales_invoice_posting_from_settings(): void
    {
        $this->salesOrders->method('findById')->with(1001)->willReturn($this->headerRecord());
        $this->settings->method('list')->willReturn([$this->settingsRecord()]);
        $this->salesOrderLines->method('list')->with(['sales_order_id' => 1001])->willReturn([
            new DataRecord([
                'id' => 501,
                'item_id' => 10,
                'description' => 'Stock sale',
                'ordered_qty' => 2,
                'unit_cost' => 40,
                'gross_amount' => 100,
                'line_total' => 90,
                'discount_amount' => 10,
                'tax_amount' => 15,
            ]),
        ]);
        $this->items->method('findByIdInTenant')->with(10, 1)->willReturn(new DataRecord([
            'id' => 10,
            'is_sellable' => true,
            'is_stockable' => true,
        ]));

        $result = $this->service->previewFinance('sales_order', 1001, ['tenant_id' => 1]);

        self::assertTrue($result->isSuccess());
        $preview = $result->valueOrFail();
        self::assertTrue($preview['balanced']);
        self::assertSame(195.0, $preview['totals']['debit_total']);
        self::assertSame(195.0, $preview['totals']['credit_total']);
        self::assertSame('sales', $preview['entry_payload']['source_module']);
        self::assertSame(1100, $preview['lines_payload'][0]['account_id']);
        self::assertSame(4000, $preview['lines_payload'][2]['account_id']);
        self::assertSame(5000, $preview['lines_payload'][4]['account_id']);
        self::assertSame(1000, $preview['lines_payload'][5]['account_id']);
    }

    public function test_preview_finance_for_non_stock_service_skips_cogs_and_inventory(): void
    {
        $this->salesOrders->method('findById')->with(1002)->willReturn($this->headerRecord(['id' => 1002]));
        $this->settings->method('list')->willReturn([$this->settingsRecord()]);
        $this->salesOrderLines->method('list')->with(['sales_order_id' => 1002])->willReturn([
            new DataRecord([
                'id' => 502,
                'item_id' => 20,
                'description' => 'Service sale',
                'ordered_qty' => 1,
                'gross_amount' => 200,
                'line_total' => 200,
                'discount_amount' => 0,
                'tax_amount' => 0,
            ]),
        ]);
        $this->items->method('findByIdInTenant')->with(20, 1)->willReturn(new DataRecord([
            'id' => 20,
            'is_sellable' => true,
            'is_stockable' => false,
        ]));

        $result = $this->service->previewFinance('sales_order', 1002, ['tenant_id' => 1]);

        self::assertTrue($result->isSuccess());
        $preview = $result->valueOrFail();
        self::assertTrue($preview['balanced']);
        self::assertCount(2, $preview['lines_payload']);
        self::assertSame(200.0, $preview['totals']['debit_total']);
        self::assertSame(200.0, $preview['totals']['credit_total']);
    }

    public function test_preview_finance_builds_balanced_sales_return_reversal(): void
    {
        $this->salesReturns->method('findById')->with(2001)->willReturn($this->headerRecord([
            'id' => 2001,
            'return_number' => 'SRET-2001',
            'status' => 'posted',
        ]));
        $this->settings->method('list')->willReturn([$this->settingsRecord()]);
        $this->salesReturnLines->method('list')->with(['sales_return_id' => 2001])->willReturn([
            new DataRecord([
                'id' => 601,
                'item_id' => 10,
                'description' => 'Returned stock',
                'return_qty' => 1,
                'unit_cost' => 40,
                'gross_amount' => 100,
                'line_total' => 90,
                'discount_amount' => 10,
                'tax_amount' => 15,
            ]),
        ]);
        $this->items->method('findByIdInTenant')->with(10, 1)->willReturn(new DataRecord([
            'id' => 10,
            'is_sellable' => true,
            'is_stockable' => true,
        ]));

        $result = $this->service->previewFinance('sales_return', 2001, ['tenant_id' => 1]);

        self::assertTrue($result->isSuccess());
        $preview = $result->valueOrFail();
        self::assertTrue($preview['balanced']);
        self::assertSame(155.0, $preview['totals']['debit_total']);
        self::assertSame(155.0, $preview['totals']['credit_total']);
        self::assertSame(4000, $preview['lines_payload'][0]['account_id']);
        self::assertSame(1100, $preview['lines_payload'][3]['account_id']);
        self::assertSame(1000, $preview['lines_payload'][4]['account_id']);
    }

    public function test_post_inventory_issues_stock_out_for_posted_gdn(): void
    {
        $this->gdns->method('findById')->with(77)->willReturn($this->headerRecord([
            'id' => 77,
            'gdn_number' => 'GDN-77',
            'status' => 'confirmed',
            'warehouse_id' => 3,
        ]));
        $this->gdnLines->method('list')->with(['gdn_header_id' => 77])->willReturn([
            new DataRecord([
                'id' => 701,
                'item_id' => 10,
                'uom_id' => 3,
                'delivered_qty' => 5,
                'unit_cost' => 20,
            ]),
        ]);
        $this->items->method('findByIdInTenant')->with(10, 1)->willReturn(new DataRecord([
            'id' => 10,
            'is_stockable' => true,
        ]));
        $this->uoms->method('findById')->with(3)->willReturn(new DataRecord(['id' => 3, 'type' => 'quantity']));
        $this->uomConversion->method('getBaseUnit')->with('quantity', 1)->willReturn(Result::success(new DataRecord(['id' => 1])));
        $this->uomConversion->method('normalizeToBase')->with(5.0, 3, 1)->willReturn(Result::success(50.0));
        $this->gdns->expects(self::once())->method('update')->with(77, self::isType('array'))->willReturn($this->headerRecord(['id' => 77]));
        $this->stockMovements->expects(self::once())->method('execute')->with(self::callback(static function (array $payload): bool {
            return $payload['source_module'] === 'sales'
                && $payload['direction'] === 'OUT'
                && (float) $payload['quantity_out'] === 5.0
                && (float) $payload['base_quantity_out'] === 50.0
                && $payload['source_reference'] === 'GDN-77';
        }))->willReturn(Result::success(new DataRecord(['id' => 1])));

        $result = $this->service->postInventory('gdn_header', 77, ['tenant_id' => 1]);

        self::assertTrue($result->isSuccess());
    }

    /** @param array<string, mixed> $overrides */
    private function headerRecord(array $overrides = []): DataRecord
    {
        return new DataRecord(array_merge([
            'id' => 1001,
            'tenant_id' => 1,
            'organization_unit_id' => 12,
            'customer_id' => 20,
            'status' => 'documented',
            'so_number' => 'SO-1001',
            'warehouse_id' => 3,
            'exchange_rate' => 1,
        ], $overrides));
    }

    private function settingsRecord(): DataRecord
    {
        return new DataRecord([
            'id' => 3,
            'default_customer_receivable_account_id' => 1100,
            'default_sales_income_account_id' => 4000,
            'default_inventory_account_id' => 1000,
            'default_cogs_account_id' => 5000,
            'default_sales_tax_account_id' => 2100,
            'default_sales_discount_account_id' => 5000,
        ]);
    }
}
