<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Purchase;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\CreateStockMovementServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseDocumentLinkRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchasePaymentAllocationRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseStatusHistoryRepositoryInterface;
use Modules\Purchase\Application\Services\PurchaseWorkflowService;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PurchaseWorkflowServiceTest extends TestCase
{
    private PurchaseOrderRepositoryInterface&MockObject $purchaseOrderRepository;
    private PurchaseOrderLineRepositoryInterface&MockObject $purchaseOrderLineRepository;
    private GrnHeaderRepositoryInterface&MockObject $grnHeaderRepository;
    private GrnLineRepositoryInterface&MockObject $grnLineRepository;
    private PurchaseReturnRepositoryInterface&MockObject $purchaseReturnRepository;
    private PurchaseReturnLineRepositoryInterface&MockObject $purchaseReturnLineRepository;
    private PurchaseDocumentLinkRepositoryInterface&MockObject $purchaseDocumentLinkRepository;
    private PurchasePaymentAllocationRepositoryInterface&MockObject $purchasePaymentAllocationRepository;
    private PurchaseStatusHistoryRepositoryInterface&MockObject $purchaseStatusHistoryRepository;
    private DocumentOrchestrator&MockObject $documentOrchestrator;
    private PaymentAllocationServiceInterface&MockObject $paymentAllocationService;
    private AdvancePaymentAllocationServiceInterface&MockObject $advancePaymentAllocationService;
    private CreateStockMovementServiceInterface&MockObject $createStockMovementService;
    private FinancePostingServiceInterface&MockObject $financePostingService;
    private PriceResolverServiceInterface&MockObject $priceResolverService;
    private ItemRepositoryInterface&MockObject $itemRepository;
    private UnitOfMeasureRepositoryInterface&MockObject $unitOfMeasureRepository;
    private UomConversionServiceInterface&MockObject $uomConversionService;

    private PurchaseWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseOrderRepository = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $this->purchaseOrderLineRepository = $this->createMock(PurchaseOrderLineRepositoryInterface::class);
        $this->grnHeaderRepository = $this->createMock(GrnHeaderRepositoryInterface::class);
        $this->grnLineRepository = $this->createMock(GrnLineRepositoryInterface::class);
        $this->purchaseReturnRepository = $this->createMock(PurchaseReturnRepositoryInterface::class);
        $this->purchaseReturnLineRepository = $this->createMock(PurchaseReturnLineRepositoryInterface::class);
        $this->purchaseDocumentLinkRepository = $this->createMock(PurchaseDocumentLinkRepositoryInterface::class);
        $this->purchasePaymentAllocationRepository = $this->createMock(
            PurchasePaymentAllocationRepositoryInterface::class,
        );
        $this->purchaseStatusHistoryRepository = $this->createMock(PurchaseStatusHistoryRepositoryInterface::class);
        $this->documentOrchestrator = $this->createMock(DocumentOrchestrator::class);
        $this->paymentAllocationService = $this->createMock(PaymentAllocationServiceInterface::class);
        $this->advancePaymentAllocationService = $this->createMock(AdvancePaymentAllocationServiceInterface::class);
        $this->createStockMovementService = $this->createMock(CreateStockMovementServiceInterface::class);
        $this->financePostingService = $this->createMock(FinancePostingServiceInterface::class);
        $this->priceResolverService = $this->createMock(PriceResolverServiceInterface::class);
        $this->itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $this->unitOfMeasureRepository = $this->createMock(UnitOfMeasureRepositoryInterface::class);
        $this->uomConversionService = $this->createMock(UomConversionServiceInterface::class);

        $this->purchaseOrderRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $this->grnHeaderRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $this->purchaseReturnRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $this->service = new PurchaseWorkflowService(
            $this->purchaseOrderRepository,
            $this->purchaseOrderLineRepository,
            $this->grnHeaderRepository,
            $this->grnLineRepository,
            $this->purchaseReturnRepository,
            $this->purchaseReturnLineRepository,
            $this->purchaseDocumentLinkRepository,
            $this->purchasePaymentAllocationRepository,
            $this->purchaseStatusHistoryRepository,
            $this->documentOrchestrator,
            $this->paymentAllocationService,
            $this->advancePaymentAllocationService,
            $this->createStockMovementService,
            $this->financePostingService,
            $this->priceResolverService,
            $this->itemRepository,
            $this->unitOfMeasureRepository,
            $this->uomConversionService,
        );
    }

    public function testTransitionFailsWhenTenantDoesNotMatch(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'status' => 'draft',
                'organization_unit_id' => 12,
            ]));

        $result = $this->service->transition('purchase_order', 10, [
            'tenant_id' => 2,
            'status' => 'submitted',
            'actor_id' => 99,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionFailsWhenStatusPathIsNotAllowed(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'status' => 'draft',
                'organization_unit_id' => 12,
            ]));

        $result = $this->service->transition('purchase_order', 10, [
            'tenant_id' => 1,
            'status' => 'documented',
            'actor_id' => 99,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionToCancelledFailsWhenActiveDependenciesExist(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(15)
            ->willReturn(new DataRecord([
                'id' => 15,
                'tenant_id' => 1,
                'status' => 'submitted',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 15,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'tenant_id' => 1,
                    'document_id' => 5001,
                    'status' => 'active',
                ]),
            ]);

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 5001,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 70,
                    'tenant_id' => 1,
                    'document_id' => 5001,
                    'allocated_amount' => 100,
                    'status' => 'active',
                ]),
            ]);

        $result = $this->service->transition('purchase_order', 15, [
            'tenant_id' => 1,
            'status' => 'cancelled',
            'actor_id' => 11,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testCreateDocumentRequiresDocumentTypeId(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'supplier_id' => 20,
                'organization_unit_id' => 12,
            ]));

        $result = $this->service->createDocument('purchase_order', 10, [
            'tenant_id' => 1,
            'actor_id' => 99,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testAllocatePaymentFailsWithoutDocumentLinkOrDocumentId(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->willReturn([]);

        $result = $this->service->allocatePayment('purchase_order', 10, [
            'tenant_id' => 1,
            'allocated_amount' => 10.0,
            'payment_id' => 5,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testPostInventoryUsesBaseUomConversion(): void
    {
        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('findById')
            ->with(99)
            ->willReturn(new DataRecord([
                'id' => 99,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'warehouse_id' => 3,
            ]));

        $this->purchaseReturnLineRepository
            ->expects(self::once())
            ->method('list')
            ->with(['purchase_return_id' => 99])
            ->willReturn([
                new DataRecord([
                    'id' => 1001,
                    'item_id' => 11,
                    'uom_id' => 3,
                    'return_qty' => 5,
                    'unit_price' => 20,
                ]),
            ]);

        $this->itemRepository
            ->expects(self::once())
            ->method('findByIdInTenant')
            ->with(11, 1)
            ->willReturn(new DataRecord(['id' => 11, 'tenant_id' => 1]));

        $this->unitOfMeasureRepository
            ->expects(self::once())
            ->method('findById')
            ->with(3)
            ->willReturn(new DataRecord(['id' => 3, 'type' => 'quantity']));

        $this->uomConversionService
            ->expects(self::once())
            ->method('getBaseUnit')
            ->with('quantity', 1)
            ->willReturn(Result::success(new DataRecord(['id' => 1, 'type' => 'quantity'])));

        $this->uomConversionService
            ->expects(self::once())
            ->method('normalizeToBase')
            ->with(5.0, 3, 1)
            ->willReturn(Result::success(50.0));

        $this->createStockMovementService
            ->expects(self::once())
            ->method('execute')
            ->with(self::callback(static function (array $payload): bool {
                return (int) ($payload['base_uom_id'] ?? 0) === 1
                    && (float) ($payload['base_quantity'] ?? 0) === 50.0
                    && (string) ($payload['direction'] ?? '') === 'OUT';
            }))
            ->willReturn(Result::success(new DataRecord(['id' => 501])));

        $result = $this->service->postInventory('purchase_return', 99, [
            'tenant_id' => 1,
            'actor_id' => 90,
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testPostFinanceRequiresEntryAndLinesPayload(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(77)
            ->willReturn(new DataRecord([
                'id' => 77,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
            ]));

        $result = $this->service->postFinance('purchase_order', 77, [
            'tenant_id' => 1,
            'entry_payload' => [],
            'lines_payload' => [],
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionToReversedRequiresReason(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(88)
            ->willReturn(new DataRecord([
                'id' => 88,
                'tenant_id' => 1,
                'status' => 'closed',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 88,
                'status' => 'active',
            ])
            ->willReturn([]);

        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'purchase_order_id' => 88,
            ])
            ->willReturn([]);

        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'original_purchase_order_id' => 88,
            ])
            ->willReturn([]);

        $result = $this->service->transition('purchase_order', 88, [
            'tenant_id' => 1,
            'status' => 'reversed',
            'actor_id' => 9,
            'finance_reversed' => true,
            'reason' => '   ',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionToReversedRequiresFinanceAcknowledgementForClosedPurchaseOrder(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(89)
            ->willReturn(new DataRecord([
                'id' => 89,
                'tenant_id' => 1,
                'status' => 'closed',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 89,
                'status' => 'active',
            ])
            ->willReturn([]);

        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'purchase_order_id' => 89,
            ])
            ->willReturn([]);

        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'original_purchase_order_id' => 89,
            ])
            ->willReturn([]);

        $result = $this->service->transition('purchase_order', 89, [
            'tenant_id' => 1,
            'status' => 'reversed',
            'actor_id' => 9,
            'reason' => 'Test reversal',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionToReversedRequiresInventoryAcknowledgementForPostedGrn(): void
    {
        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(55)
            ->willReturn(new DataRecord([
                'id' => 55,
                'tenant_id' => 1,
                'status' => 'posted',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'grn_header',
                'source_id' => 55,
                'status' => 'active',
            ])
            ->willReturn([]);

        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'original_grn_id' => 55,
            ])
            ->willReturn([]);

        $result = $this->service->transition('grn_header', 55, [
            'tenant_id' => 1,
            'status' => 'reversed',
            'actor_id' => 9,
            'reason' => 'Reverse after stock rollback',
            'finance_reversed' => true,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionToCancelledFailsWhenPurchaseOrderHasUnfinalizedGrnDependency(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(91)
            ->willReturn(new DataRecord([
                'id' => 91,
                'tenant_id' => 1,
                'status' => 'submitted',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 91,
                'status' => 'active',
            ])
            ->willReturn([]);

        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'purchase_order_id' => 91,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 120,
                    'status' => 'posted',
                ]),
            ]);

        $result = $this->service->transition('purchase_order', 91, [
            'tenant_id' => 1,
            'status' => 'cancelled',
            'actor_id' => 1,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testTransitionToReversedFailsWhenGrnHasUnfinalizedReturnDependency(): void
    {
        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(92)
            ->willReturn(new DataRecord([
                'id' => 92,
                'tenant_id' => 1,
                'status' => 'documented',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'grn_header',
                'source_id' => 92,
                'status' => 'active',
            ])
            ->willReturn([]);

        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'original_grn_id' => 92,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 210,
                    'status' => 'posted',
                ]),
            ]);

        $result = $this->service->transition('grn_header', 92, [
            'tenant_id' => 1,
            'status' => 'reversed',
            'actor_id' => 9,
            'reason' => 'reverse',
            'finance_reversed' => true,
            'inventory_reversed' => true,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }
}
