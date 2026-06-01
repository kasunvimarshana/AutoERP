<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Purchase;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Entities\Document;
use Modules\Document\Domain\Entities\DocumentItem;
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
use Modules\Purchase\Application\Repositories\PurchaseSettingRepositoryInterface;
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

    private PurchaseSettingRepositoryInterface&MockObject $purchaseSettingRepository;

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
        $this->purchaseSettingRepository = $this->createMock(PurchaseSettingRepositoryInterface::class);
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
        $this->purchaseStatusHistoryRepository
            ->method('create')
            ->willReturn(new DataRecord(['id' => 1]));

        $this->service = new PurchaseWorkflowService(
            $this->purchaseOrderRepository,
            $this->purchaseOrderLineRepository,
            $this->grnHeaderRepository,
            $this->grnLineRepository,
            $this->purchaseReturnRepository,
            $this->purchaseReturnLineRepository,
            $this->purchaseDocumentLinkRepository,
            $this->purchasePaymentAllocationRepository,
            $this->purchaseSettingRepository,
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

    public function test_transition_fails_when_tenant_does_not_match(): void
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

    public function test_transition_fails_when_status_path_is_not_allowed(): void
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

    public function test_transition_to_cancelled_fails_when_active_dependencies_exist(): void
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

    public function test_create_document_requires_document_type_id(): void
    {
        $this->purchaseSettingRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->willReturnOnConsecutiveCalls([], []);

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

    public function test_allocate_payment_fails_without_document_link_or_document_id(): void
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

    public function test_post_inventory_uses_base_uom_conversion(): void
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

    public function test_post_finance_requires_entry_and_lines_payload(): void
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

    public function test_transition_to_reversed_requires_reason(): void
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

    public function test_transition_to_reversed_requires_finance_acknowledgement_for_closed_purchase_order(): void
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

    public function test_transition_to_reversed_requires_inventory_acknowledgement_for_posted_grn(): void
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

    public function test_transition_to_cancelled_fails_when_purchase_order_has_unfinalized_grn_dependency(): void
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

    public function test_transition_to_reversed_fails_when_grn_has_unfinalized_return_dependency(): void
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

    public function test_transition_to_reversed_succeeds_when_document_links_exist_but_no_active_allocations(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(120)
            ->willReturn(new DataRecord([
                'id' => 120,
                'tenant_id' => 1,
                'status' => 'documented',
                'organization_unit_id' => 12,
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 120,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 301,
                    'tenant_id' => 1,
                    'document_id' => 9001,
                    'status' => 'active',
                ]),
            ]);

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 9001,
                'status' => 'active',
            ])
            ->willReturn([]);

        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'purchase_order_id' => 120,
            ])
            ->willReturn([]);

        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'original_purchase_order_id' => 120,
            ])
            ->willReturn([]);

        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('update')
            ->with(120, self::callback(static function (array $payload): bool {
                return (string) ($payload['status'] ?? '') === 'reversed'
                    && (string) ($payload['document_status'] ?? '') === 'reversed';
            }))
            ->willReturn(new DataRecord([
                'id' => 120,
                'tenant_id' => 1,
                'status' => 'reversed',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return (string) ($payload['entity_type'] ?? '') === 'purchase_order'
                    && (string) ($payload['from_status'] ?? '') === 'documented'
                    && (string) ($payload['to_status'] ?? '') === 'reversed';
            }))
            ->willReturn(new DataRecord(['id' => 1]));

        $result = $this->service->transition('purchase_order', 120, [
            'tenant_id' => 1,
            'status' => 'reversed',
            'actor_id' => 9,
            'reason' => 'Document rollback completed',
            'finance_reversed' => true,
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function test_allocate_payment_fails_when_payment_and_advance_payment_are_both_provided(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $result = $this->service->allocatePayment('purchase_order', 10, [
            'tenant_id' => 1,
            'document_id' => 500,
            'payment_id' => 41,
            'advance_payment_id' => 51,
            'allocated_amount' => 100,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'Use either payment_id or advance_payment_id, not both.',
            $result->errorOrFail()->message,
        );
    }

    public function test_create_document_fails_when_settings_require_grn_before_direct_purchase_document(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(33)
            ->willReturn(new DataRecord([
                'id' => 33,
                'tenant_id' => 1,
                'supplier_id' => 20,
                'organization_unit_id' => 12,
                'status' => 'confirmed',
            ]));

        $this->purchaseSettingRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'is_active' => true,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'tenant_id' => 1,
                    'allow_direct_purchase_document' => false,
                ]),
            ]);

        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'purchase_order_id' => 33,
            ])
            ->willReturn([]);

        $result = $this->service->createDocument('purchase_order', 33, [
            'tenant_id' => 1,
            'document_type_id' => 44,
            'actor_id' => 77,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function test_create_document_uses_settings_default_document_type_when_not_provided(): void
    {
        $this->purchaseDocumentLinkRepository
            ->method('list')
            ->willReturn([]);

        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('findById')
            ->with(72)
            ->willReturn(new DataRecord([
                'id' => 72,
                'tenant_id' => 1,
                'supplier_id' => 20,
                'organization_unit_id' => 12,
                'status' => 'approved',
                'return_number' => 'PR-72',
            ]));

        $this->purchaseSettingRepository
            ->expects(self::once())
            ->method('list')
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'tenant_id' => 1,
                    'purchase_return_document_definition_id' => 77,
                ]),
            ]);

        $this->documentOrchestrator
            ->expects(self::once())
            ->method('listDocumentDefinitions')
            ->with(1)
            ->willReturn([
                ['id' => 77, 'document_type_id' => 44],
            ]);

        $this->purchaseReturnLineRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->with(['purchase_return_id' => 72])
            ->willReturn([
                new DataRecord([
                    'id' => 801,
                    'item_id' => 11,
                    'uom_id' => 3,
                    'return_qty' => 2,
                    'unit_price' => 15,
                    'line_total_with_tax' => 30,
                ]),
            ]);

        $this->purchaseDocumentLinkRepository
            ->method('create')
            ->willReturn(new DataRecord(['id' => 1]));

        $this->documentOrchestrator
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function ($dto): bool {
                return (int) ($dto->documentTypeId ?? 0) === 44
                    && (int) ($dto->tenantId ?? 0) === 1;
            }))
            ->willReturn(new DocumentAggregate(
                new Document(
                    id: 9901,
                    tenantId: 1,
                    organizationUnitId: 12,
                    documentTypeId: 44,
                    documentNumber: 'PDR-0001',
                    documentDate: '2026-05-28',
                    dueDate: null,
                    status: 'draft',
                    ownerId: 7,
                    partyId: 20,
                    subtotal: '30.0000',
                    discountTotal: '0.0000',
                    taxTotal: '0.0000',
                    grandTotal: '30.0000',
                    data: [],
                    notes: null,
                    createdBy: 7,
                    updatedBy: 7,
                ),
                [
                    new DocumentItem(
                        id: 5001,
                        documentId: 9901,
                        itemType: 'purchase_line',
                        description: null,
                        lineTotal: '30.0000',
                        sequence: 1,
                        data: [
                            'source_line_id' => 801,
                            'quantity' => 2,
                        ],
                    ),
                ],
            ));

        $result = $this->service->createDocument('purchase_return', 72, [
            'tenant_id' => 1,
            'actor_id' => 7,
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
        self::assertSame(9901, (int) (($result->valueOrFail()['document_id'] ?? 0)));
    }

    public function test_create_document_fails_when_requested_quantity_exceeds_available_line_quantity(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(501)
            ->willReturn(new DataRecord([
                'id' => 501,
                'tenant_id' => 1,
                'supplier_id' => 20,
                'organization_unit_id' => 12,
                'status' => 'confirmed',
            ]));

        $this->purchaseSettingRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->willReturnOnConsecutiveCalls([], []);

        $this->purchaseOrderLineRepository
            ->expects(self::once())
            ->method('list')
            ->with(['purchase_order_id' => 501])
            ->willReturn([
                new DataRecord([
                    'id' => 900,
                    'ordered_qty' => 5,
                ]),
            ]);

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 501,
                'source_line_id' => 900,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'linked_quantity' => 4,
                ]),
            ]);

        $result = $this->service->createDocument('purchase_order', 501, [
            'tenant_id' => 1,
            'document_type_id' => 11,
            'items' => [
                [
                    'item_type' => 'purchase_line',
                    'line_total' => 100,
                    'data' => [
                        'source_line_id' => 900,
                        'quantity' => 2,
                    ],
                ],
            ],
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'Document quantity exceeds available quantity for one or more source lines.',
            $result->errorOrFail()->message,
        );
    }

    public function test_allocate_payment_fails_when_allocation_exceeds_linked_document_amount(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(601)
            ->willReturn(new DataRecord([
                'id' => 601,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 2001,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'source_line_id' => null,
                    'document_line_id' => null,
                    'linked_amount' => 100,
                ]),
            ]);

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 2001,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 91,
                    'allocated_amount' => 90,
                    'status' => 'active',
                ]),
            ]);

        $result = $this->service->allocatePayment('purchase_order', 601, [
            'tenant_id' => 1,
            'document_id' => 2001,
            'payment_id' => 41,
            'allocated_amount' => 15,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame('Allocation exceeds document allocatable amount.', $result->errorOrFail()->message);
    }

    public function test_allocate_payment_succeeds_at_allocation_boundary(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(602)
            ->willReturn(new DataRecord([
                'id' => 602,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 2002,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'source_line_id' => null,
                    'document_line_id' => null,
                    'linked_amount' => 100,
                ]),
            ]);

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 2002,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 92,
                    'allocated_amount' => 90,
                    'status' => 'active',
                ]),
            ]);

        $this->paymentAllocationService
            ->expects(self::once())
            ->method('createAllocation')
            ->with(self::callback(static function (array $payload): bool {
                return (int) ($payload['payment_id'] ?? 0) === 41
                    && (float) ($payload['allocated_amount'] ?? 0) === 10.0
                    && (int) ($payload['document_id'] ?? 0) === 2002;
            }))
            ->willReturn(Result::success(['allocation_id' => 555]));

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return (int) ($payload['document_id'] ?? 0) === 2002
                    && (float) ($payload['allocated_amount'] ?? 0) === 10.0
                    && (float) ($payload['base_allocated_amount'] ?? 0) === 10.0;
            }))
            ->willReturn(new DataRecord(['id' => 93]));

        $result = $this->service->allocatePayment('purchase_order', 602, [
            'tenant_id' => 1,
            'document_id' => 2002,
            'payment_id' => 41,
            'allocated_amount' => 10,
            'actor_id' => 7,
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function test_create_document_returns_idempotent_replay_when_key_already_exists(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(700)
            ->willReturn(new DataRecord([
                'id' => 700,
                'tenant_id' => 1,
                'supplier_id' => 20,
                'organization_unit_id' => 12,
                'status' => 'confirmed',
            ]));

        $this->purchaseSettingRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->willReturnOnConsecutiveCalls([], []);

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 700,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'document_id' => 4444,
                    'source_line_id' => null,
                    'document_line_id' => null,
                    'metadata' => ['idempotency_key' => 'idem-doc-1'],
                ]),
            ]);

        $this->documentOrchestrator
            ->expects(self::once())
            ->method('show')
            ->with(1, 4444)
            ->willReturn(new DocumentAggregate(
                new Document(
                    id: 4444,
                    tenantId: 1,
                    organizationUnitId: 12,
                    documentTypeId: 44,
                    documentNumber: 'PINV-4444',
                    documentDate: '2026-05-28',
                    dueDate: null,
                    status: 'draft',
                    ownerId: 7,
                    partyId: 20,
                    subtotal: '100.0000',
                    discountTotal: '0.0000',
                    taxTotal: '0.0000',
                    grandTotal: '100.0000',
                    data: [],
                    notes: null,
                    createdBy: 7,
                    updatedBy: 7,
                ),
                [],
            ));

        $this->documentOrchestrator
            ->expects(self::never())
            ->method('create');

        $result = $this->service->createDocument('purchase_order', 700, [
            'tenant_id' => 1,
            'idempotency_key' => 'idem-doc-1',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertSame(4444, (int) ($result->valueOrFail()['document_id'] ?? 0));
        self::assertTrue((bool) ($result->valueOrFail()['idempotent_replay'] ?? false));
    }

    public function test_create_document_fails_when_idempotency_key_is_reused_with_different_payload(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(703)
            ->willReturn(new DataRecord([
                'id' => 703,
                'tenant_id' => 1,
                'supplier_id' => 20,
                'organization_unit_id' => 12,
                'status' => 'confirmed',
            ]));

        $this->purchaseSettingRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->willReturnOnConsecutiveCalls([], []);

        $this->purchaseDocumentLinkRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'source_type' => 'purchase_order',
                'source_id' => 703,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'document_id' => 4444,
                    'source_line_id' => null,
                    'document_line_id' => null,
                    'metadata' => [
                        'idempotency_key' => 'idem-doc-conflict-1',
                        'idempotency_signature' => 'other-signature',
                    ],
                ]),
            ]);

        $result = $this->service->createDocument('purchase_order', 703, [
            'tenant_id' => 1,
            'document_type_id' => 44,
            'idempotency_key' => 'idem-doc-conflict-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_allocate_payment_returns_idempotent_replay_when_key_already_exists(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(701)
            ->willReturn(new DataRecord([
                'id' => 701,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 9001,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 6001,
                    'metadata' => ['idempotency_key' => 'idem-pay-1'],
                    'allocated_amount' => 10,
                ]),
            ]);

        $this->paymentAllocationService
            ->expects(self::never())
            ->method('createAllocation');

        $result = $this->service->allocatePayment('purchase_order', 701, [
            'tenant_id' => 1,
            'document_id' => 9001,
            'payment_id' => 41,
            'allocated_amount' => 10,
            'idempotency_key' => 'idem-pay-1',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertSame(6001, (int) ($result->valueOrFail()['allocation_id'] ?? 0));
        self::assertTrue((bool) ($result->valueOrFail()['idempotent_replay'] ?? false));
    }

    public function test_allocate_payment_fails_when_idempotency_key_is_reused_with_different_payload(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(704)
            ->willReturn(new DataRecord([
                'id' => 704,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchasePaymentAllocationRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'document_id' => 9002,
                'status' => 'active',
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 6002,
                    'metadata' => [
                        'idempotency_key' => 'idem-pay-conflict-1',
                        'idempotency_signature' => 'different-signature',
                    ],
                ]),
            ]);

        $result = $this->service->allocatePayment('purchase_order', 704, [
            'tenant_id' => 1,
            'document_id' => 9002,
            'payment_id' => 41,
            'allocated_amount' => 10,
            'idempotency_key' => 'idem-pay-conflict-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_post_inventory_returns_idempotent_replay_when_key_already_exists(): void
    {
        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('findById')
            ->with(801)
            ->willReturn(new DataRecord([
                'id' => 801,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'posted',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_return',
                'entity_id' => 801,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'metadata' => [
                        'idempotency_key' => 'idem-inv-1',
                        'workflow_action' => 'inventory_post',
                    ],
                ]),
            ]);

        $this->createStockMovementService
            ->expects(self::never())
            ->method('execute');

        $result = $this->service->postInventory('purchase_return', 801, [
            'tenant_id' => 1,
            'idempotency_key' => 'idem-inv-1',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertTrue((bool) ($result->valueOrFail()['idempotent_replay'] ?? false));
    }

    public function test_post_inventory_fails_when_idempotency_key_is_reused_with_different_payload(): void
    {
        $this->grnHeaderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(801)
            ->willReturn(new DataRecord([
                'id' => 801,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'approved',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'grn_header',
                'entity_id' => 801,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 11,
                    'metadata' => [
                        'idempotency_key' => 'idem-inv-conflict-1',
                        'workflow_action' => 'inventory_post',
                        'idempotency_signature' => 'existing-signature',
                    ],
                ]),
            ]);

        $result = $this->service->postInventory('grn_header', 801, [
            'tenant_id' => 1,
            'movement_type' => 'in',
            'warehouse_id' => 5,
            'idempotency_key' => 'idem-inv-conflict-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_post_finance_returns_idempotent_replay_when_key_already_exists(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(802)
            ->willReturn(new DataRecord([
                'id' => 802,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 802,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 2,
                    'metadata' => [
                        'idempotency_key' => 'idem-fin-1',
                        'workflow_action' => 'finance_post',
                    ],
                ]),
            ]);

        $this->financePostingService
            ->expects(self::never())
            ->method('postFromSource');

        $result = $this->service->postFinance('purchase_order', 802, [
            'tenant_id' => 1,
            'entry_payload' => ['memo' => 'purchase accrual'],
            'lines_payload' => [['account_id' => 1, 'debit' => 100]],
            'idempotency_key' => 'idem-fin-1',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertTrue((bool) ($result->valueOrFail()['idempotent_replay'] ?? false));
    }

    public function test_post_finance_fails_when_idempotency_key_is_reused_with_different_payload(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(802)
            ->willReturn(new DataRecord([
                'id' => 802,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 802,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 12,
                    'metadata' => [
                        'idempotency_key' => 'idem-fin-conflict-1',
                        'workflow_action' => 'finance_post',
                        'idempotency_signature' => 'existing-signature',
                    ],
                ]),
            ]);

        $result = $this->service->postFinance('purchase_order', 802, [
            'tenant_id' => 1,
            'entry_payload' => ['memo' => 'changed payload'],
            'lines_payload' => [['account_id' => 5, 'debit' => 25]],
            'idempotency_key' => 'idem-fin-conflict-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_transition_returns_idempotent_replay_when_history_exists_for_same_target_status(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(901)
            ->willReturn(new DataRecord([
                'id' => 901,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'draft',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 901,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 1,
                    'to_status' => 'submitted',
                    'metadata' => [
                        'idempotency_key' => 'idem-tr-1',
                        'workflow_action' => 'transition',
                        'target_status' => 'submitted',
                    ],
                ]),
            ]);

        $this->purchaseOrderRepository
            ->expects(self::never())
            ->method('update');

        $result = $this->service->transition('purchase_order', 901, [
            'tenant_id' => 1,
            'status' => 'submitted',
            'idempotency_key' => 'idem-tr-1',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function test_transition_fails_when_idempotency_key_is_reused_with_different_target_status(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(903)
            ->willReturn(new DataRecord([
                'id' => 903,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'draft',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 903,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 3,
                    'to_status' => 'submitted',
                    'metadata' => [
                        'idempotency_key' => 'idem-tr-2',
                        'workflow_action' => 'transition',
                        'target_status' => 'submitted',
                    ],
                ]),
            ]);

        $result = $this->service->transition('purchase_order', 903, [
            'tenant_id' => 1,
            'status' => 'approved',
            'idempotency_key' => 'idem-tr-2',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_transition_fails_when_idempotency_key_signature_conflicts(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(905)
            ->willReturn(new DataRecord([
                'id' => 905,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'draft',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 905,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 5,
                    'to_status' => 'submitted',
                    'metadata' => [
                        'idempotency_key' => 'idem-tr-sig-1',
                        'workflow_action' => 'transition',
                        'target_status' => 'submitted',
                        'idempotency_signature' => 'existing-signature',
                    ],
                ]),
            ]);

        $result = $this->service->transition('purchase_order', 905, [
            'tenant_id' => 1,
            'status' => 'submitted',
            'idempotency_key' => 'idem-tr-sig-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_reverse_finance_returns_idempotent_replay_when_key_already_exists(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(902)
            ->willReturn(new DataRecord([
                'id' => 902,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 902,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 2,
                    'metadata' => [
                        'idempotency_key' => 'idem-rf-1',
                        'workflow_action' => 'finance_reverse',
                        'journal_entry_id' => '777',
                    ],
                ]),
            ]);

        $this->financePostingService
            ->expects(self::never())
            ->method('reverseByEntryId');

        $result = $this->service->reverseFinance('purchase_order', 902, [
            'tenant_id' => 1,
            'journal_entry_id' => 777,
            'idempotency_key' => 'idem-rf-1',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertTrue((bool) ($result->valueOrFail()['idempotent_replay'] ?? false));
    }

    public function test_reverse_finance_fails_when_idempotency_key_is_reused_with_different_journal_entry_id(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(904)
            ->willReturn(new DataRecord([
                'id' => 904,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 904,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 4,
                    'metadata' => [
                        'idempotency_key' => 'idem-rf-2',
                        'workflow_action' => 'finance_reverse',
                        'journal_entry_id' => '888',
                    ],
                ]),
            ]);

        $this->financePostingService
            ->expects(self::never())
            ->method('reverseByEntryId');

        $result = $this->service->reverseFinance('purchase_order', 904, [
            'tenant_id' => 1,
            'journal_entry_id' => 889,
            'idempotency_key' => 'idem-rf-2',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_reverse_finance_fails_when_idempotency_key_signature_conflicts(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(906)
            ->willReturn(new DataRecord([
                'id' => 906,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 906,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 6,
                    'metadata' => [
                        'idempotency_key' => 'idem-rf-sig-1',
                        'workflow_action' => 'finance_reverse',
                        'journal_entry_id' => '900',
                        'idempotency_signature' => 'existing-signature',
                    ],
                ]),
            ]);

        $result = $this->service->reverseFinance('purchase_order', 906, [
            'tenant_id' => 1,
            'journal_entry_id' => 900,
            'idempotency_key' => 'idem-rf-sig-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key is already used with a different request payload.',
            $result->errorOrFail()->message,
        );
    }

    public function test_reverse_finance_fails_when_idempotent_history_lacks_replay_journal_metadata(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(907)
            ->willReturn(new DataRecord([
                'id' => 907,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'status' => 'documented',
            ]));

        $this->purchaseStatusHistoryRepository
            ->expects(self::once())
            ->method('list')
            ->with([
                'tenant_id' => 1,
                'entity_type' => 'purchase_order',
                'entity_id' => 907,
            ])
            ->willReturn([
                new DataRecord([
                    'id' => 7,
                    'metadata' => [
                        'idempotency_key' => 'idem-rf-legacy-1',
                        'workflow_action' => 'finance_reverse',
                    ],
                ]),
            ]);

        $this->financePostingService
            ->expects(self::never())
            ->method('reverseByEntryId');

        $result = $this->service->reverseFinance('purchase_order', 907, [
            'tenant_id' => 1,
            'journal_entry_id' => 901,
            'idempotency_key' => 'idem-rf-legacy-1',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(PurchaseErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
        self::assertSame(
            'idempotency_key cannot be safely replayed due to missing finance reversal metadata.',
            $result->errorOrFail()->message,
        );
    }

    public function test_preview_finance_builds_balanced_supplier_invoice_posting_from_settings(): void
    {
        $this->purchaseOrderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1001)
            ->willReturn(new DataRecord([
                'id' => 1001,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'supplier_id' => 20,
                'status' => 'documented',
                'po_number' => 'PO-1001',
                'exchange_rate' => 1,
            ]));

        $this->purchaseSettingRepository
            ->expects(self::once())
            ->method('list')
            ->willReturn([
                new DataRecord([
                    'id' => 3,
                    'default_supplier_payable_account_id' => 2000,
                    'default_inventory_account_id' => 1200,
                    'default_purchase_account_id' => 5000,
                    'default_purchase_tax_account_id' => 1300,
                    'default_purchase_discount_account_id' => 5100,
                ]),
            ]);

        $this->purchaseOrderLineRepository
            ->expects(self::once())
            ->method('list')
            ->with(['purchase_order_id' => 1001])
            ->willReturn([
                new DataRecord([
                    'id' => 501,
                    'item_id' => 10,
                    'description' => 'Stock part',
                    'gross_amount' => 100,
                    'line_total' => 90,
                    'discount_amount' => 10,
                    'tax_amount' => 15,
                ]),
            ]);

        $this->itemRepository
            ->expects(self::once())
            ->method('findByIdInTenant')
            ->with(10, 1)
            ->willReturn(new DataRecord([
                'id' => 10,
                'is_purchasable' => true,
                'is_stockable' => true,
            ]));

        $result = $this->service->postFinance('purchase_order', 1001, [
            'tenant_id' => 1,
            'preview_only' => true,
        ]);

        self::assertTrue($result->isSuccess());
        $preview = $result->valueOrFail();
        self::assertTrue($preview['balanced']);
        self::assertSame(115.0, $preview['totals']['debit_total']);
        self::assertSame(115.0, $preview['totals']['credit_total']);
        self::assertSame('purchase', $preview['entry_payload']['source_module']);
        self::assertSame(1200, $preview['lines_payload'][0]['account_id']);
        self::assertSame(5100, $preview['lines_payload'][2]['account_id']);
        self::assertSame(2000, $preview['lines_payload'][3]['account_id']);
    }

    public function test_preview_finance_builds_balanced_purchase_return_reversal(): void
    {
        $this->purchaseReturnRepository
            ->expects(self::once())
            ->method('findById')
            ->with(2001)
            ->willReturn(new DataRecord([
                'id' => 2001,
                'tenant_id' => 1,
                'organization_unit_id' => 12,
                'supplier_id' => 20,
                'status' => 'posted',
                'return_number' => 'PRET-2001',
                'exchange_rate' => 1,
            ]));

        $this->purchaseSettingRepository
            ->expects(self::once())
            ->method('list')
            ->willReturn([
                new DataRecord([
                    'id' => 4,
                    'default_supplier_payable_account_id' => 2000,
                    'default_inventory_account_id' => 1200,
                    'default_purchase_account_id' => 5000,
                    'default_purchase_tax_account_id' => 1300,
                    'default_purchase_discount_account_id' => 5100,
                ]),
            ]);

        $this->purchaseReturnLineRepository
            ->expects(self::once())
            ->method('list')
            ->with(['purchase_return_id' => 2001])
            ->willReturn([
                new DataRecord([
                    'id' => 601,
                    'item_id' => 10,
                    'description' => 'Returned stock part',
                    'gross_amount' => 100,
                    'line_total' => 90,
                    'discount_amount' => 10,
                    'tax_amount' => 15,
                ]),
            ]);

        $this->itemRepository
            ->expects(self::once())
            ->method('findByIdInTenant')
            ->with(10, 1)
            ->willReturn(new DataRecord([
                'id' => 10,
                'is_purchasable' => true,
                'is_stockable' => true,
            ]));

        $result = $this->service->postFinance('purchase_return', 2001, [
            'tenant_id' => 1,
            'preview_only' => true,
        ]);

        self::assertTrue($result->isSuccess());
        $preview = $result->valueOrFail();
        self::assertTrue($preview['balanced']);
        self::assertSame(115.0, $preview['totals']['debit_total']);
        self::assertSame(115.0, $preview['totals']['credit_total']);
        self::assertSame(2000, $preview['lines_payload'][0]['account_id']);
        self::assertSame(1200, $preview['lines_payload'][2]['account_id']);
        self::assertSame(1300, $preview['lines_payload'][3]['account_id']);
    }
}
