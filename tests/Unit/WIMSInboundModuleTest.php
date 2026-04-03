<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// ── PurchaseOrder ────────────────────────────────────────────────────────────
use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderStatus;
use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderLineStatus;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderCreated;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderSubmitted;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderApproved;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderUpdated;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderDeleted;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderCancelled;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderLineCreated;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderLineUpdated;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderLineDeleted;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderLineNotFoundException;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderData;
use Modules\PurchaseOrder\Application\DTOs\UpdatePurchaseOrderData;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderLineData;
use Modules\PurchaseOrder\Application\DTOs\UpdatePurchaseOrderLineData;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\SubmitPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Services\CreatePurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\FindPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\SubmitPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\ApprovePurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\CancelPurchaseOrderService;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository;
use Modules\PurchaseOrder\Infrastructure\Http\Controllers\PurchaseOrderController;
use Modules\PurchaseOrder\Infrastructure\Http\Controllers\PurchaseOrderLineController;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\StorePurchaseOrderRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\StorePurchaseOrderLineRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\UpdatePurchaseOrderLineRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderResource;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderCollection;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderLineResource;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderLineCollection;
use Modules\PurchaseOrder\Infrastructure\Providers\PurchaseOrderServiceProvider;

// ── GoodsReceipt ─────────────────────────────────────────────────────────────
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptStatus;
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptLineStatus;
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptLineCondition;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptCreated;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptReceived;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptApproved;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptUpdated;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptDeleted;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptCancelled;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptLineCreated;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptLineUpdated;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptLineDeleted;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptLineNotFoundException;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptData;
use Modules\GoodsReceipt\Application\DTOs\UpdateGoodsReceiptData;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptLineData;
use Modules\GoodsReceipt\Application\DTOs\UpdateGoodsReceiptLineData;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\ReceiveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\ApproveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CancelGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Services\CreateGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\FindGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\ReceiveGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\ApproveGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\CancelGoodsReceiptService;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptRepository;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptLineRepository;
use Modules\GoodsReceipt\Infrastructure\Http\Controllers\GoodsReceiptController;
use Modules\GoodsReceipt\Infrastructure\Http\Controllers\GoodsReceiptLineController;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\StoreGoodsReceiptRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\UpdateGoodsReceiptRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\StoreGoodsReceiptLineRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\UpdateGoodsReceiptLineRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptResource;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptCollection;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptLineResource;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptLineCollection;
use Modules\GoodsReceipt\Infrastructure\Providers\GoodsReceiptServiceProvider;

// ── Core base classes ────────────────────────────────────────────────────────
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * WIMSInboundModuleTest
 *
 * Validates the PurchaseOrder and GoodsReceipt modules which together implement
 * the Inbound Flow of the enterprise Warehouse & Inventory Management System.
 */
class WIMSInboundModuleTest extends TestCase
{
    // ========================================================================
    // PURCHASE ORDER — VALUE OBJECTS
    // ========================================================================

    public function test_purchase_order_status_constants(): void
    {
        $this->assertSame('draft', PurchaseOrderStatus::DRAFT);
        $this->assertSame('submitted', PurchaseOrderStatus::SUBMITTED);
        $this->assertSame('approved', PurchaseOrderStatus::APPROVED);
        $this->assertSame('partially_received', PurchaseOrderStatus::PARTIALLY_RECEIVED);
        $this->assertSame('fully_received', PurchaseOrderStatus::FULLY_RECEIVED);
        $this->assertSame('cancelled', PurchaseOrderStatus::CANCELLED);
        $this->assertSame('closed', PurchaseOrderStatus::CLOSED);
    }

    public function test_purchase_order_status_values(): void
    {
        $values = PurchaseOrderStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('submitted', $values);
        $this->assertContains('approved', $values);
        $this->assertContains('partially_received', $values);
        $this->assertContains('fully_received', $values);
        $this->assertContains('cancelled', $values);
        $this->assertContains('closed', $values);
        $this->assertCount(7, $values);
    }

    public function test_purchase_order_line_status_constants(): void
    {
        $this->assertSame('open', PurchaseOrderLineStatus::OPEN);
        $this->assertSame('partially_received', PurchaseOrderLineStatus::PARTIALLY_RECEIVED);
        $this->assertSame('fully_received', PurchaseOrderLineStatus::FULLY_RECEIVED);
        $this->assertSame('cancelled', PurchaseOrderLineStatus::CANCELLED);
    }

    public function test_purchase_order_line_status_values(): void
    {
        $values = PurchaseOrderLineStatus::values();
        $this->assertContains('open', $values);
        $this->assertContains('partially_received', $values);
        $this->assertContains('fully_received', $values);
        $this->assertContains('cancelled', $values);
        $this->assertCount(4, $values);
    }

    // ========================================================================
    // PURCHASE ORDER — ENTITY
    // ========================================================================

    public function test_purchase_order_entity_defaults(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-001',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $this->assertEquals(1, $order->getTenantId());
        $this->assertEquals('PO-001', $order->getReferenceNumber());
        $this->assertEquals(5, $order->getSupplierId());
        $this->assertEquals('2026-04-01', $order->getOrderDate());
        $this->assertEquals('draft', $order->getStatus());
        $this->assertTrue($order->isDraft());
        $this->assertFalse($order->isApproved());
        $this->assertFalse($order->isCancelled());
        $this->assertEquals('USD', $order->getCurrency());
        $this->assertEquals(0.0, $order->getSubtotal());
        $this->assertEquals(0.0, $order->getTaxAmount());
        $this->assertEquals(0.0, $order->getDiscountAmount());
        $this->assertEquals(0.0, $order->getTotalAmount());
        $this->assertNull($order->getId());
        $this->assertNull($order->getSupplierReference());
        $this->assertNull($order->getExpectedDate());
        $this->assertNull($order->getWarehouseId());
        $this->assertNull($order->getNotes());
        $this->assertNull($order->getApprovedBy());
        $this->assertNull($order->getApprovedAt());
        $this->assertNull($order->getSubmittedBy());
        $this->assertNull($order->getSubmittedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getUpdatedAt());
    }

    public function test_purchase_order_entity_with_all_fields(): void
    {
        $order = new PurchaseOrder(
            tenantId: 2,
            referenceNumber: 'PO-002',
            supplierId: 10,
            orderDate: '2026-04-01',
            supplierReference: 'SUP-REF-001',
            expectedDate: '2026-04-15',
            warehouseId: 3,
            currency: 'EUR',
            subtotal: 1000.0,
            taxAmount: 100.0,
            discountAmount: 50.0,
            totalAmount: 1050.0,
            notes: 'Test purchase order',
        );

        $this->assertEquals(2, $order->getTenantId());
        $this->assertEquals('SUP-REF-001', $order->getSupplierReference());
        $this->assertEquals('2026-04-15', $order->getExpectedDate());
        $this->assertEquals(3, $order->getWarehouseId());
        $this->assertEquals('EUR', $order->getCurrency());
        $this->assertEquals(1000.0, $order->getSubtotal());
        $this->assertEquals(100.0, $order->getTaxAmount());
        $this->assertEquals(50.0, $order->getDiscountAmount());
        $this->assertEquals(1050.0, $order->getTotalAmount());
        $this->assertEquals('Test purchase order', $order->getNotes());
    }

    public function test_purchase_order_submit_transitions_status(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-003',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $this->assertTrue($order->isDraft());
        $order->submit(42);
        $this->assertEquals('submitted', $order->getStatus());
        $this->assertEquals(42, $order->getSubmittedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getSubmittedAt());
    }

    public function test_purchase_order_approve_transitions_status(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-004',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $order->approve(99);
        $this->assertTrue($order->isApproved());
        $this->assertEquals('approved', $order->getStatus());
        $this->assertEquals(99, $order->getApprovedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getApprovedAt());
    }

    public function test_purchase_order_cancel_transitions_status(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-005',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $order->cancel();
        $this->assertTrue($order->isCancelled());
        $this->assertEquals('cancelled', $order->getStatus());
    }

    public function test_purchase_order_mark_partially_received(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-006',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $order->markPartiallyReceived();
        $this->assertEquals('partially_received', $order->getStatus());
        $this->assertFalse($order->isDraft());
    }

    public function test_purchase_order_mark_fully_received(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-007',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $order->markFullyReceived();
        $this->assertEquals('fully_received', $order->getStatus());
    }

    public function test_purchase_order_close_transitions_status(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-008',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $order->markFullyReceived();
        $order->close();
        $this->assertEquals('closed', $order->getStatus());
    }

    public function test_purchase_order_update_details(): void
    {
        $order = new PurchaseOrder(
            tenantId: 1,
            referenceNumber: 'PO-009',
            supplierId: 5,
            orderDate: '2026-04-01',
        );

        $order->updateDetails(
            supplierReference: 'NEW-REF',
            expectedDate: '2026-05-01',
            warehouseId: 7,
            notes: 'Updated notes',
            metadataArray: ['priority' => 'high'],
        );

        $this->assertEquals('NEW-REF', $order->getSupplierReference());
        $this->assertEquals('2026-05-01', $order->getExpectedDate());
        $this->assertEquals(7, $order->getWarehouseId());
        $this->assertEquals('Updated notes', $order->getNotes());
    }

    // ========================================================================
    // PURCHASE ORDER LINE — ENTITY
    // ========================================================================

    public function test_purchase_order_line_entity_defaults(): void
    {
        $line = new PurchaseOrderLine(
            tenantId: 1,
            purchaseOrderId: 10,
            lineNumber: 1,
            productId: 100,
            quantityOrdered: 50.0,
            unitPrice: 10.0,
        );

        $this->assertEquals(1, $line->getTenantId());
        $this->assertEquals(10, $line->getPurchaseOrderId());
        $this->assertEquals(1, $line->getLineNumber());
        $this->assertEquals(100, $line->getProductId());
        $this->assertEquals(50.0, $line->getQuantityOrdered());
        $this->assertEquals(10.0, $line->getUnitPrice());
        $this->assertEquals(0.0, $line->getQuantityReceived());
        $this->assertEquals(0.0, $line->getDiscountPercent());
        $this->assertEquals(0.0, $line->getTaxPercent());
        $this->assertEquals(0.0, $line->getLineTotal());
        $this->assertEquals('open', $line->getStatus());
        $this->assertTrue($line->isOpen());
        $this->assertFalse($line->isFullyReceived());
        $this->assertNull($line->getId());
        $this->assertNull($line->getVariationId());
        $this->assertNull($line->getDescription());
        $this->assertNull($line->getUomId());
        $this->assertNull($line->getExpectedDate());
        $this->assertNull($line->getNotes());
        $this->assertNull($line->getMetadata());
    }

    public function test_purchase_order_line_mark_partially_received(): void
    {
        $line = new PurchaseOrderLine(
            tenantId: 1,
            purchaseOrderId: 10,
            lineNumber: 1,
            productId: 100,
            quantityOrdered: 50.0,
            unitPrice: 10.0,
        );

        $line->markPartiallyReceived(25.0);
        $this->assertEquals('partially_received', $line->getStatus());
        $this->assertEquals(25.0, $line->getQuantityReceived());
        $this->assertFalse($line->isOpen());
        $this->assertFalse($line->isFullyReceived());
    }

    public function test_purchase_order_line_mark_fully_received(): void
    {
        $line = new PurchaseOrderLine(
            tenantId: 1,
            purchaseOrderId: 10,
            lineNumber: 1,
            productId: 100,
            quantityOrdered: 50.0,
            unitPrice: 10.0,
        );

        $line->markFullyReceived(50.0);
        $this->assertEquals('fully_received', $line->getStatus());
        $this->assertEquals(50.0, $line->getQuantityReceived());
        $this->assertTrue($line->isFullyReceived());
    }

    public function test_purchase_order_line_cancel(): void
    {
        $line = new PurchaseOrderLine(
            tenantId: 1,
            purchaseOrderId: 10,
            lineNumber: 1,
            productId: 100,
            quantityOrdered: 50.0,
            unitPrice: 10.0,
        );

        $line->cancel();
        $this->assertEquals('cancelled', $line->getStatus());
        $this->assertFalse($line->isOpen());
    }

    public function test_purchase_order_line_receive_quantity_partial(): void
    {
        $line = new PurchaseOrderLine(
            tenantId: 1,
            purchaseOrderId: 10,
            lineNumber: 1,
            productId: 100,
            quantityOrdered: 50.0,
            unitPrice: 10.0,
        );

        $line->receiveQuantity(20.0);
        $this->assertEquals('partially_received', $line->getStatus());
        $this->assertEquals(20.0, $line->getQuantityReceived());
    }

    public function test_purchase_order_line_receive_quantity_full(): void
    {
        $line = new PurchaseOrderLine(
            tenantId: 1,
            purchaseOrderId: 10,
            lineNumber: 1,
            productId: 100,
            quantityOrdered: 50.0,
            unitPrice: 10.0,
        );

        $line->receiveQuantity(50.0);
        $this->assertEquals('fully_received', $line->getStatus());
        $this->assertTrue($line->isFullyReceived());
    }

    // ========================================================================
    // PURCHASE ORDER — EVENTS
    // ========================================================================

    public function test_purchase_order_created_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderCreated::class));
        $this->assertTrue(is_a(PurchaseOrderCreated::class, BaseEvent::class, true));
    }

    public function test_purchase_order_submitted_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderSubmitted::class));
        $this->assertTrue(is_a(PurchaseOrderSubmitted::class, BaseEvent::class, true));
    }

    public function test_purchase_order_approved_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderApproved::class));
        $this->assertTrue(is_a(PurchaseOrderApproved::class, BaseEvent::class, true));
    }

    public function test_purchase_order_updated_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderUpdated::class));
        $this->assertTrue(is_a(PurchaseOrderUpdated::class, BaseEvent::class, true));
    }

    public function test_purchase_order_deleted_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderDeleted::class));
        $this->assertTrue(is_a(PurchaseOrderDeleted::class, BaseEvent::class, true));
    }

    public function test_purchase_order_cancelled_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderCancelled::class));
        $this->assertTrue(is_a(PurchaseOrderCancelled::class, BaseEvent::class, true));
    }

    public function test_purchase_order_line_created_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineCreated::class));
        $this->assertTrue(is_a(PurchaseOrderLineCreated::class, BaseEvent::class, true));
    }

    public function test_purchase_order_line_updated_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineUpdated::class));
        $this->assertTrue(is_a(PurchaseOrderLineUpdated::class, BaseEvent::class, true));
    }

    public function test_purchase_order_line_deleted_event_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineDeleted::class));
        $this->assertTrue(is_a(PurchaseOrderLineDeleted::class, BaseEvent::class, true));
    }

    // ========================================================================
    // PURCHASE ORDER — EXCEPTIONS
    // ========================================================================

    public function test_purchase_order_not_found_exception_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderNotFoundException::class));
    }

    public function test_purchase_order_line_not_found_exception_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineNotFoundException::class));
    }

    // ========================================================================
    // PURCHASE ORDER — DTOs
    // ========================================================================

    public function test_purchase_order_data_dto_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderData::class));
        $this->assertTrue(is_a(PurchaseOrderData::class, BaseDto::class, true));
    }

    public function test_update_purchase_order_data_dto_import(): void
    {
        $this->assertTrue(class_exists(UpdatePurchaseOrderData::class));
        $this->assertTrue(is_a(UpdatePurchaseOrderData::class, BaseDto::class, true));
    }

    public function test_purchase_order_line_data_dto_import(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineData::class));
        $this->assertTrue(is_a(PurchaseOrderLineData::class, BaseDto::class, true));
    }

    public function test_update_purchase_order_line_data_dto_import(): void
    {
        $this->assertTrue(class_exists(UpdatePurchaseOrderLineData::class));
        $this->assertTrue(is_a(UpdatePurchaseOrderLineData::class, BaseDto::class, true));
    }

    // ========================================================================
    // PURCHASE ORDER — SERVICE CONTRACTS
    // ========================================================================

    public function test_create_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(CreatePurchaseOrderServiceInterface::class));
    }

    public function test_find_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(FindPurchaseOrderServiceInterface::class));
    }

    public function test_update_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(UpdatePurchaseOrderServiceInterface::class));
    }

    public function test_delete_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(DeletePurchaseOrderServiceInterface::class));
    }

    public function test_submit_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(SubmitPurchaseOrderServiceInterface::class));
    }

    public function test_approve_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(ApprovePurchaseOrderServiceInterface::class));
    }

    public function test_cancel_purchase_order_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(CancelPurchaseOrderServiceInterface::class));
    }

    public function test_create_purchase_order_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(CreatePurchaseOrderLineServiceInterface::class));
    }

    public function test_find_purchase_order_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(FindPurchaseOrderLineServiceInterface::class));
    }

    public function test_update_purchase_order_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(UpdatePurchaseOrderLineServiceInterface::class));
    }

    public function test_delete_purchase_order_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(DeletePurchaseOrderLineServiceInterface::class));
    }

    // ========================================================================
    // PURCHASE ORDER — SERVICES EXTEND BASE SERVICE
    // ========================================================================

    public function test_create_purchase_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CreatePurchaseOrderService::class, BaseService::class, true));
    }

    public function test_find_purchase_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(FindPurchaseOrderService::class, BaseService::class, true));
    }

    public function test_submit_purchase_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(SubmitPurchaseOrderService::class, BaseService::class, true));
    }

    public function test_approve_purchase_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(ApprovePurchaseOrderService::class, BaseService::class, true));
    }

    public function test_cancel_purchase_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CancelPurchaseOrderService::class, BaseService::class, true));
    }

    // ========================================================================
    // PURCHASE ORDER — INFRASTRUCTURE
    // ========================================================================

    public function test_purchase_order_model_table(): void
    {
        $model = new PurchaseOrderModel;
        $this->assertEquals('purchase_orders', $model->getTable());
    }

    public function test_purchase_order_line_model_table(): void
    {
        $model = new PurchaseOrderLineModel;
        $this->assertEquals('purchase_order_lines', $model->getTable());
    }

    public function test_purchase_order_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentPurchaseOrderRepository::class,
            PurchaseOrderRepositoryInterface::class,
            true
        ));
    }

    public function test_purchase_order_line_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentPurchaseOrderLineRepository::class,
            PurchaseOrderLineRepositoryInterface::class,
            true
        ));
    }

    public function test_purchase_order_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderController::class));
    }

    public function test_purchase_order_line_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineController::class));
    }

    public function test_store_purchase_order_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StorePurchaseOrderRequest::class));
    }

    public function test_update_purchase_order_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdatePurchaseOrderRequest::class));
    }

    public function test_store_purchase_order_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StorePurchaseOrderLineRequest::class));
    }

    public function test_update_purchase_order_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdatePurchaseOrderLineRequest::class));
    }

    public function test_purchase_order_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderResource::class));
    }

    public function test_purchase_order_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderCollection::class));
    }

    public function test_purchase_order_line_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineResource::class));
    }

    public function test_purchase_order_line_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderLineCollection::class));
    }

    public function test_purchase_order_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(PurchaseOrderServiceProvider::class));
    }

    // ========================================================================
    // GOODS RECEIPT — VALUE OBJECTS
    // ========================================================================

    public function test_goods_receipt_status_constants(): void
    {
        $this->assertSame('draft', GoodsReceiptStatus::DRAFT);
        $this->assertSame('pending', GoodsReceiptStatus::PENDING);
        $this->assertSame('partially_received', GoodsReceiptStatus::PARTIALLY_RECEIVED);
        $this->assertSame('fully_received', GoodsReceiptStatus::FULLY_RECEIVED);
        $this->assertSame('approved', GoodsReceiptStatus::APPROVED);
        $this->assertSame('under_inspection', GoodsReceiptStatus::UNDER_INSPECTION);
        $this->assertSame('inspected', GoodsReceiptStatus::INSPECTED);
        $this->assertSame('put_away', GoodsReceiptStatus::PUT_AWAY);
        $this->assertSame('cancelled', GoodsReceiptStatus::CANCELLED);
    }

    public function test_goods_receipt_status_values(): void
    {
        $values = GoodsReceiptStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('pending', $values);
        $this->assertContains('partially_received', $values);
        $this->assertContains('fully_received', $values);
        $this->assertContains('approved', $values);
        $this->assertContains('under_inspection', $values);
        $this->assertContains('inspected', $values);
        $this->assertContains('put_away', $values);
        $this->assertContains('cancelled', $values);
        $this->assertCount(9, $values);
    }

    public function test_goods_receipt_line_status_constants(): void
    {
        $this->assertSame('pending', GoodsReceiptLineStatus::PENDING);
        $this->assertSame('accepted', GoodsReceiptLineStatus::ACCEPTED);
        $this->assertSame('rejected', GoodsReceiptLineStatus::REJECTED);
        $this->assertSame('partially_accepted', GoodsReceiptLineStatus::PARTIALLY_ACCEPTED);
    }

    public function test_goods_receipt_line_status_values(): void
    {
        $values = GoodsReceiptLineStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('accepted', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('partially_accepted', $values);
        $this->assertCount(4, $values);
    }

    public function test_goods_receipt_line_condition_constants(): void
    {
        $this->assertSame('good', GoodsReceiptLineCondition::GOOD);
        $this->assertSame('damaged', GoodsReceiptLineCondition::DAMAGED);
        $this->assertSame('expired', GoodsReceiptLineCondition::EXPIRED);
        $this->assertSame('quarantine', GoodsReceiptLineCondition::QUARANTINE);
    }

    public function test_goods_receipt_line_condition_values(): void
    {
        $values = GoodsReceiptLineCondition::values();
        $this->assertContains('good', $values);
        $this->assertContains('damaged', $values);
        $this->assertContains('expired', $values);
        $this->assertContains('quarantine', $values);
        $this->assertCount(4, $values);
    }

    // ========================================================================
    // GOODS RECEIPT — ENTITY
    // ========================================================================

    public function test_goods_receipt_entity_defaults(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-001',
            supplierId: 5,
        );

        $this->assertEquals(1, $receipt->getTenantId());
        $this->assertEquals('GR-001', $receipt->getReferenceNumber());
        $this->assertEquals(5, $receipt->getSupplierId());
        $this->assertEquals('draft', $receipt->getStatus());
        $this->assertTrue($receipt->isDraft());
        $this->assertFalse($receipt->isApproved());
        $this->assertFalse($receipt->isCancelled());
        $this->assertEquals('USD', $receipt->getCurrency());
        $this->assertNull($receipt->getId());
        $this->assertNull($receipt->getPurchaseOrderId());
        $this->assertNull($receipt->getWarehouseId());
        $this->assertNull($receipt->getReceivedDate());
        $this->assertNull($receipt->getNotes());
        $this->assertNull($receipt->getReceivedBy());
        $this->assertNull($receipt->getApprovedBy());
        $this->assertNull($receipt->getApprovedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $receipt->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $receipt->getUpdatedAt());
    }

    public function test_goods_receipt_entity_with_all_fields(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 2,
            referenceNumber: 'GR-002',
            supplierId: 10,
            purchaseOrderId: 20,
            warehouseId: 3,
            receivedDate: new \DateTimeImmutable('2026-04-01'),
            currency: 'GBP',
            notes: 'Inbound shipment notes',
        );

        $this->assertEquals(2, $receipt->getTenantId());
        $this->assertEquals(20, $receipt->getPurchaseOrderId());
        $this->assertEquals(3, $receipt->getWarehouseId());
        $this->assertInstanceOf(\DateTimeInterface::class, $receipt->getReceivedDate());
        $this->assertEquals('GBP', $receipt->getCurrency());
        $this->assertEquals('Inbound shipment notes', $receipt->getNotes());
    }

    public function test_goods_receipt_receive_transitions_status(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-003',
            supplierId: 5,
        );

        $this->assertTrue($receipt->isDraft());
        $receipt->receive(42);
        $this->assertEquals('pending', $receipt->getStatus());
        $this->assertEquals(42, $receipt->getReceivedBy());
        $this->assertFalse($receipt->isDraft());
    }

    public function test_goods_receipt_approve_transitions_status(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-004',
            supplierId: 5,
        );

        $receipt->approve(99);
        $this->assertTrue($receipt->isApproved());
        $this->assertEquals('approved', $receipt->getStatus());
        $this->assertEquals(99, $receipt->getApprovedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $receipt->getApprovedAt());
    }

    public function test_goods_receipt_cancel_transitions_status(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-005',
            supplierId: 5,
        );

        $receipt->cancel();
        $this->assertTrue($receipt->isCancelled());
        $this->assertEquals('cancelled', $receipt->getStatus());
    }

    public function test_goods_receipt_mark_partially_received(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-006',
            supplierId: 5,
        );

        $receipt->markPartiallyReceived();
        $this->assertEquals('partially_received', $receipt->getStatus());
        $this->assertFalse($receipt->isDraft());
    }

    public function test_goods_receipt_mark_fully_received(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-007',
            supplierId: 5,
        );

        $receipt->markFullyReceived();
        $this->assertEquals('fully_received', $receipt->getStatus());
    }

    public function test_goods_receipt_update_details(): void
    {
        $receipt = new GoodsReceipt(
            tenantId: 1,
            referenceNumber: 'GR-008',
            supplierId: 5,
        );

        $receipt->updateDetails(
            notes: 'Updated notes',
            metadata: ['carrier' => 'DHL'],
            warehouseId: 7,
            receivedDate: new \DateTimeImmutable('2026-04-02'),
        );

        $this->assertEquals('Updated notes', $receipt->getNotes());
        $this->assertEquals(7, $receipt->getWarehouseId());
        $this->assertInstanceOf(\DateTimeInterface::class, $receipt->getReceivedDate());
    }

    // ========================================================================
    // GOODS RECEIPT LINE — ENTITY
    // ========================================================================

    public function test_goods_receipt_line_entity_defaults(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 1,
            productId: 100,
            quantityReceived: 20.0,
        );

        $this->assertEquals(1, $line->getTenantId());
        $this->assertEquals(10, $line->getGoodsReceiptId());
        $this->assertEquals(1, $line->getLineNumber());
        $this->assertEquals(100, $line->getProductId());
        $this->assertEquals(20.0, $line->getQuantityReceived());
        $this->assertEquals(0.0, $line->getQuantityExpected());
        $this->assertEquals(0.0, $line->getQuantityAccepted());
        $this->assertEquals(0.0, $line->getQuantityRejected());
        $this->assertEquals(0.0, $line->getUnitCost());
        $this->assertEquals('good', $line->getCondition());
        $this->assertEquals('pending', $line->getStatus());
        $this->assertNull($line->getId());
        $this->assertNull($line->getPurchaseOrderLineId());
        $this->assertNull($line->getVariationId());
        $this->assertNull($line->getBatchId());
        $this->assertNull($line->getSerialNumber());
        $this->assertNull($line->getUomId());
        $this->assertNull($line->getNotes());
        $this->assertNull($line->getPutawayLocationId());
        $this->assertInstanceOf(\DateTimeInterface::class, $line->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $line->getUpdatedAt());
    }

    public function test_goods_receipt_line_with_batch_and_serial(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 2,
            productId: 200,
            quantityReceived: 10.0,
            purchaseOrderLineId: 5,
            variationId: 3,
            batchId: 7,
            serialNumber: 'SN-ABC-001',
            uomId: 4,
            quantityExpected: 10.0,
            unitCost: 25.0,
            condition: 'good',
        );

        $this->assertEquals(5, $line->getPurchaseOrderLineId());
        $this->assertEquals(3, $line->getVariationId());
        $this->assertEquals(7, $line->getBatchId());
        $this->assertEquals('SN-ABC-001', $line->getSerialNumber());
        $this->assertEquals(4, $line->getUomId());
        $this->assertEquals(10.0, $line->getQuantityExpected());
        $this->assertEquals(25.0, $line->getUnitCost());
        $this->assertEquals('good', $line->getCondition());
    }

    public function test_goods_receipt_line_accept(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 1,
            productId: 100,
            quantityReceived: 20.0,
        );

        $line->accept(20.0);
        $this->assertEquals('accepted', $line->getStatus());
        $this->assertEquals(20.0, $line->getQuantityAccepted());
        $this->assertTrue($line->isAccepted());
        $this->assertFalse($line->isRejected());
    }

    public function test_goods_receipt_line_reject(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 1,
            productId: 100,
            quantityReceived: 20.0,
        );

        $line->reject(20.0);
        $this->assertEquals('rejected', $line->getStatus());
        $this->assertEquals(20.0, $line->getQuantityRejected());
        $this->assertTrue($line->isRejected());
        $this->assertFalse($line->isAccepted());
    }

    public function test_goods_receipt_line_partial_accept(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 1,
            productId: 100,
            quantityReceived: 20.0,
        );

        $line->partialAccept(15.0, 5.0);
        $this->assertEquals('partially_accepted', $line->getStatus());
        $this->assertEquals(15.0, $line->getQuantityAccepted());
        $this->assertEquals(5.0, $line->getQuantityRejected());
        $this->assertFalse($line->isAccepted());
        $this->assertFalse($line->isRejected());
    }

    public function test_goods_receipt_line_set_putaway_location(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 1,
            productId: 100,
            quantityReceived: 20.0,
        );

        $this->assertNull($line->getPutawayLocationId());
        $line->setPutawayLocation(99);
        $this->assertEquals(99, $line->getPutawayLocationId());
    }

    public function test_goods_receipt_line_condition_damaged(): void
    {
        $line = new GoodsReceiptLine(
            tenantId: 1,
            goodsReceiptId: 10,
            lineNumber: 1,
            productId: 100,
            quantityReceived: 5.0,
            condition: 'damaged',
        );

        $this->assertEquals('damaged', $line->getCondition());
    }

    // ========================================================================
    // GOODS RECEIPT — EVENTS
    // ========================================================================

    public function test_goods_receipt_created_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptCreated::class));
        $this->assertTrue(is_a(GoodsReceiptCreated::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_received_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptReceived::class));
        $this->assertTrue(is_a(GoodsReceiptReceived::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_approved_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptApproved::class));
        $this->assertTrue(is_a(GoodsReceiptApproved::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_updated_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptUpdated::class));
        $this->assertTrue(is_a(GoodsReceiptUpdated::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_deleted_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptDeleted::class));
        $this->assertTrue(is_a(GoodsReceiptDeleted::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_cancelled_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptCancelled::class));
        $this->assertTrue(is_a(GoodsReceiptCancelled::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_line_created_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineCreated::class));
        $this->assertTrue(is_a(GoodsReceiptLineCreated::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_line_updated_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineUpdated::class));
        $this->assertTrue(is_a(GoodsReceiptLineUpdated::class, BaseEvent::class, true));
    }

    public function test_goods_receipt_line_deleted_event_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineDeleted::class));
        $this->assertTrue(is_a(GoodsReceiptLineDeleted::class, BaseEvent::class, true));
    }

    // ========================================================================
    // GOODS RECEIPT — EXCEPTIONS
    // ========================================================================

    public function test_goods_receipt_not_found_exception_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptNotFoundException::class));
    }

    public function test_goods_receipt_line_not_found_exception_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineNotFoundException::class));
    }

    // ========================================================================
    // GOODS RECEIPT — DTOs
    // ========================================================================

    public function test_goods_receipt_data_dto_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptData::class));
        $this->assertTrue(is_a(GoodsReceiptData::class, BaseDto::class, true));
    }

    public function test_update_goods_receipt_data_dto_import(): void
    {
        $this->assertTrue(class_exists(UpdateGoodsReceiptData::class));
        $this->assertTrue(is_a(UpdateGoodsReceiptData::class, BaseDto::class, true));
    }

    public function test_goods_receipt_line_data_dto_import(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineData::class));
        $this->assertTrue(is_a(GoodsReceiptLineData::class, BaseDto::class, true));
    }

    public function test_update_goods_receipt_line_data_dto_import(): void
    {
        $this->assertTrue(class_exists(UpdateGoodsReceiptLineData::class));
        $this->assertTrue(is_a(UpdateGoodsReceiptLineData::class, BaseDto::class, true));
    }

    // ========================================================================
    // GOODS RECEIPT — SERVICE CONTRACTS
    // ========================================================================

    public function test_create_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(CreateGoodsReceiptServiceInterface::class));
    }

    public function test_find_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(FindGoodsReceiptServiceInterface::class));
    }

    public function test_update_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(UpdateGoodsReceiptServiceInterface::class));
    }

    public function test_delete_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(DeleteGoodsReceiptServiceInterface::class));
    }

    public function test_receive_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(ReceiveGoodsReceiptServiceInterface::class));
    }

    public function test_approve_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(ApproveGoodsReceiptServiceInterface::class));
    }

    public function test_cancel_goods_receipt_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(CancelGoodsReceiptServiceInterface::class));
    }

    public function test_create_goods_receipt_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(CreateGoodsReceiptLineServiceInterface::class));
    }

    public function test_find_goods_receipt_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(FindGoodsReceiptLineServiceInterface::class));
    }

    public function test_update_goods_receipt_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(UpdateGoodsReceiptLineServiceInterface::class));
    }

    public function test_delete_goods_receipt_line_service_interface_import(): void
    {
        $this->assertTrue(interface_exists(DeleteGoodsReceiptLineServiceInterface::class));
    }

    // ========================================================================
    // GOODS RECEIPT — SERVICES EXTEND BASE SERVICE
    // ========================================================================

    public function test_create_goods_receipt_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CreateGoodsReceiptService::class, BaseService::class, true));
    }

    public function test_find_goods_receipt_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(FindGoodsReceiptService::class, BaseService::class, true));
    }

    public function test_receive_goods_receipt_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(ReceiveGoodsReceiptService::class, BaseService::class, true));
    }

    public function test_approve_goods_receipt_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(ApproveGoodsReceiptService::class, BaseService::class, true));
    }

    public function test_cancel_goods_receipt_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CancelGoodsReceiptService::class, BaseService::class, true));
    }

    // ========================================================================
    // GOODS RECEIPT — INFRASTRUCTURE
    // ========================================================================

    public function test_goods_receipt_model_table(): void
    {
        $model = new GoodsReceiptModel;
        $this->assertEquals('goods_receipts', $model->getTable());
    }

    public function test_goods_receipt_line_model_table(): void
    {
        $model = new GoodsReceiptLineModel;
        $this->assertEquals('goods_receipt_lines', $model->getTable());
    }

    public function test_goods_receipt_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentGoodsReceiptRepository::class,
            GoodsReceiptRepositoryInterface::class,
            true
        ));
    }

    public function test_goods_receipt_line_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentGoodsReceiptLineRepository::class,
            GoodsReceiptLineRepositoryInterface::class,
            true
        ));
    }

    public function test_goods_receipt_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptController::class));
    }

    public function test_goods_receipt_line_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineController::class));
    }

    public function test_store_goods_receipt_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreGoodsReceiptRequest::class));
    }

    public function test_update_goods_receipt_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateGoodsReceiptRequest::class));
    }

    public function test_store_goods_receipt_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreGoodsReceiptLineRequest::class));
    }

    public function test_update_goods_receipt_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateGoodsReceiptLineRequest::class));
    }

    public function test_goods_receipt_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptResource::class));
    }

    public function test_goods_receipt_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptCollection::class));
    }

    public function test_goods_receipt_line_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineResource::class));
    }

    public function test_goods_receipt_line_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptLineCollection::class));
    }

    public function test_goods_receipt_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptServiceProvider::class));
    }
}
