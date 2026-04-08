<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// ── SalesOrder ──────────────────────────────────────────────────────────────
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderLineStatus;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Entities\SalesOrderLine;
use Modules\SalesOrder\Domain\Events\SalesOrderCreated;
use Modules\SalesOrder\Domain\Events\SalesOrderConfirmed;
use Modules\SalesOrder\Domain\Events\SalesOrderUpdated;
use Modules\SalesOrder\Domain\Events\SalesOrderDeleted;
use Modules\SalesOrder\Domain\Events\SalesOrderPickingStarted;
use Modules\SalesOrder\Domain\Events\SalesOrderPackingStarted;
use Modules\SalesOrder\Domain\Events\SalesOrderShipped;
use Modules\SalesOrder\Domain\Events\SalesOrderDelivered;
use Modules\SalesOrder\Domain\Events\SalesOrderCancelled;
use Modules\SalesOrder\Domain\Events\SalesOrderLineCreated;
use Modules\SalesOrder\Domain\Events\SalesOrderLineUpdated;
use Modules\SalesOrder\Domain\Events\SalesOrderLineDeleted;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderLineNotFoundException;
use Modules\SalesOrder\Application\DTOs\SalesOrderData;
use Modules\SalesOrder\Application\DTOs\UpdateSalesOrderData;
use Modules\SalesOrder\Application\DTOs\SalesOrderLineData;
use Modules\SalesOrder\Application\DTOs\UpdateSalesOrderLineData;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ShipSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeliverSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Services\CreateSalesOrderService;
use Modules\SalesOrder\Application\Services\FindSalesOrderService;
use Modules\SalesOrder\Application\Services\ConfirmSalesOrderService;
use Modules\SalesOrder\Application\Services\CancelSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPickingSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPackingSalesOrderService;
use Modules\SalesOrder\Application\Services\ShipSalesOrderService;
use Modules\SalesOrder\Application\Services\DeliverSalesOrderService;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderLineRepository;
use Modules\SalesOrder\Infrastructure\Http\Controllers\SalesOrderController;
use Modules\SalesOrder\Infrastructure\Http\Controllers\SalesOrderLineController;
use Modules\SalesOrder\Infrastructure\Http\Requests\StoreSalesOrderRequest;
use Modules\SalesOrder\Infrastructure\Http\Requests\UpdateSalesOrderRequest;
use Modules\SalesOrder\Infrastructure\Http\Requests\StoreSalesOrderLineRequest;
use Modules\SalesOrder\Infrastructure\Http\Requests\UpdateSalesOrderLineRequest;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderResource;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderCollection;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderLineResource;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderLineCollection;
use Modules\SalesOrder\Infrastructure\Providers\SalesOrderServiceProvider;

// ── Dispatch ─────────────────────────────────────────────────────────────────
use Modules\Dispatch\Domain\ValueObjects\DispatchStatus;
use Modules\Dispatch\Domain\ValueObjects\DispatchLineStatus;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Entities\DispatchLine;
use Modules\Dispatch\Domain\Events\DispatchCreated;
use Modules\Dispatch\Domain\Events\DispatchConfirmed;
use Modules\Dispatch\Domain\Events\DispatchUpdated;
use Modules\Dispatch\Domain\Events\DispatchDeleted;
use Modules\Dispatch\Domain\Events\DispatchShipped;
use Modules\Dispatch\Domain\Events\DispatchDelivered;
use Modules\Dispatch\Domain\Events\DispatchCancelled;
use Modules\Dispatch\Domain\Events\DispatchLineCreated;
use Modules\Dispatch\Domain\Events\DispatchLineUpdated;
use Modules\Dispatch\Domain\Events\DispatchLineDeleted;
use Modules\Dispatch\Domain\Exceptions\DispatchNotFoundException;
use Modules\Dispatch\Domain\Exceptions\DispatchLineNotFoundException;
use Modules\Dispatch\Application\DTOs\DispatchData;
use Modules\Dispatch\Application\DTOs\UpdateDispatchData;
use Modules\Dispatch\Application\DTOs\DispatchLineData;
use Modules\Dispatch\Application\DTOs\UpdateDispatchLineData;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\FindDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\UpdateDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DeleteDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\ConfirmDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\CancelDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\ShipDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DeliverDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\CreateDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\FindDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\UpdateDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\DeleteDispatchLineServiceInterface;
use Modules\Dispatch\Application\Services\CreateDispatchService;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchRepository;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchLineRepository;
use Modules\Dispatch\Infrastructure\Http\Controllers\DispatchController;
use Modules\Dispatch\Infrastructure\Http\Controllers\DispatchLineController;
use Modules\Dispatch\Infrastructure\Http\Requests\StoreDispatchRequest;
use Modules\Dispatch\Infrastructure\Http\Requests\UpdateDispatchRequest;
use Modules\Dispatch\Infrastructure\Http\Requests\StoreDispatchLineRequest;
use Modules\Dispatch\Infrastructure\Http\Requests\UpdateDispatchLineRequest;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchResource;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchCollection;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchLineResource;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchLineCollection;
use Modules\Dispatch\Infrastructure\Providers\DispatchServiceProvider;

// ── Core base classes ────────────────────────────────────────────────────────
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * WIMSOutboundModuleTest
 *
 * Validates the SalesOrder and Dispatch modules which together implement
 * the Outbound Flow of the enterprise Warehouse & Inventory Management System.
 */
class WIMSOutboundModuleTest extends TestCase
{
    // ========================================================================
    // SALES ORDER — VALUE OBJECTS
    // ========================================================================

    public function test_sales_order_status_constants(): void
    {
        $this->assertSame('draft', SalesOrderStatus::DRAFT);
        $this->assertSame('confirmed', SalesOrderStatus::CONFIRMED);
        $this->assertSame('picking', SalesOrderStatus::PICKING);
        $this->assertSame('packing', SalesOrderStatus::PACKING);
        $this->assertSame('shipped', SalesOrderStatus::SHIPPED);
        $this->assertSame('delivered', SalesOrderStatus::DELIVERED);
        $this->assertSame('cancelled', SalesOrderStatus::CANCELLED);
    }

    public function test_sales_order_status_values(): void
    {
        $values = SalesOrderStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('confirmed', $values);
        $this->assertContains('picking', $values);
        $this->assertContains('packing', $values);
        $this->assertContains('shipped', $values);
        $this->assertContains('delivered', $values);
        $this->assertContains('cancelled', $values);
        $this->assertCount(7, $values);
    }

    public function test_sales_order_line_status_constants(): void
    {
        $this->assertSame('pending', SalesOrderLineStatus::PENDING);
        $this->assertSame('cancelled', SalesOrderLineStatus::CANCELLED);
    }

    public function test_sales_order_line_status_values(): void
    {
        $values = SalesOrderLineStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('cancelled', $values);
        $this->assertIsArray($values);
    }

    // ========================================================================
    // SALES ORDER — ENTITY
    // ========================================================================

    public function test_sales_order_entity_defaults(): void
    {
        $order = new SalesOrder(
            tenantId: 1,
            referenceNumber: 'SO-001',
            customerId: 10,
            orderDate: '2026-04-01',
        );

        $this->assertEquals(1, $order->getTenantId());
        $this->assertEquals('SO-001', $order->getReferenceNumber());
        $this->assertEquals(10, $order->getCustomerId());
        $this->assertEquals('2026-04-01', $order->getOrderDate());
        $this->assertEquals('draft', $order->getStatus());
        $this->assertTrue($order->isDraft());
        $this->assertFalse($order->isConfirmed());
        $this->assertFalse($order->isCancelled());
        $this->assertEquals('USD', $order->getCurrency());
        $this->assertEquals(0.0, $order->getSubtotal());
        $this->assertEquals(0.0, $order->getTaxAmount());
        $this->assertEquals(0.0, $order->getDiscountAmount());
        $this->assertEquals(0.0, $order->getTotalAmount());
        $this->assertNull($order->getId());
        $this->assertNull($order->getCustomerReference());
        $this->assertNull($order->getRequiredDate());
        $this->assertNull($order->getWarehouseId());
        $this->assertNull($order->getShippingAddress());
        $this->assertNull($order->getNotes());
        $this->assertNull($order->getConfirmedBy());
        $this->assertNull($order->getConfirmedAt());
        $this->assertNull($order->getShippedBy());
        $this->assertNull($order->getShippedAt());
        $this->assertNull($order->getDeliveredAt());
    }

    public function test_sales_order_entity_confirm(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->confirm(42);

        $this->assertEquals('confirmed', $order->getStatus());
        $this->assertTrue($order->isConfirmed());
        $this->assertEquals(42, $order->getConfirmedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getConfirmedAt());
    }

    public function test_sales_order_entity_start_picking(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->confirm(1);
        $order->startPicking();

        $this->assertEquals('picking', $order->getStatus());
    }

    public function test_sales_order_entity_start_packing(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->confirm(1);
        $order->startPicking();
        $order->startPacking();

        $this->assertEquals('packing', $order->getStatus());
    }

    public function test_sales_order_entity_ship(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->ship(7);

        $this->assertEquals('shipped', $order->getStatus());
        $this->assertEquals(7, $order->getShippedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getShippedAt());
    }

    public function test_sales_order_entity_deliver(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->ship(7);
        $order->deliver();

        $this->assertEquals('delivered', $order->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getDeliveredAt());
    }

    public function test_sales_order_entity_cancel(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->cancel();

        $this->assertEquals('cancelled', $order->getStatus());
        $this->assertTrue($order->isCancelled());
    }

    public function test_sales_order_entity_update_details(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');
        $order->updateDetails(
            customerReference: 'CUST-REF-001',
            requiredDate: '2026-04-10',
            warehouseId: 3,
            shippingAddress: ['street' => '123 Main St', 'city' => 'New York'],
            notes: 'Priority delivery',
            metadataArray: ['priority' => 'high'],
        );

        $this->assertEquals('CUST-REF-001', $order->getCustomerReference());
        $this->assertEquals('2026-04-10', $order->getRequiredDate());
        $this->assertEquals(3, $order->getWarehouseId());
        $this->assertEquals(['street' => '123 Main St', 'city' => 'New York'], $order->getShippingAddress());
        $this->assertEquals('Priority delivery', $order->getNotes());
    }

    public function test_sales_order_with_all_fields(): void
    {
        $order = new SalesOrder(
            tenantId: 1,
            referenceNumber: 'SO-002',
            customerId: 10,
            orderDate: '2026-04-01',
            customerReference: 'CUST-PO-001',
            requiredDate: '2026-04-15',
            warehouseId: 5,
            currency: 'EUR',
            subtotal: 1000.00,
            taxAmount: 150.00,
            discountAmount: 50.00,
            totalAmount: 1100.00,
            shippingAddress: ['country' => 'US'],
            notes: 'Handle with care',
        );

        $this->assertEquals('EUR', $order->getCurrency());
        $this->assertEquals(1000.00, $order->getSubtotal());
        $this->assertEquals(150.00, $order->getTaxAmount());
        $this->assertEquals(50.00, $order->getDiscountAmount());
        $this->assertEquals(1100.00, $order->getTotalAmount());
        $this->assertEquals(['country' => 'US'], $order->getShippingAddress());
        $this->assertEquals('CUST-PO-001', $order->getCustomerReference());
    }

    public function test_sales_order_timestamps_set_by_default(): void
    {
        $order = new SalesOrder(tenantId: 1, referenceNumber: 'SO-001', customerId: 10, orderDate: '2026-04-01');

        $this->assertInstanceOf(\DateTimeInterface::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getUpdatedAt());
    }

    // ========================================================================
    // SALES ORDER LINE — ENTITY
    // ========================================================================

    public function test_sales_order_line_entity_defaults(): void
    {
        $line = new SalesOrderLine(
            tenantId: 1,
            salesOrderId: 100,
            productId: 5,
            quantity: 10.0,
            unitPrice: 25.0,
        );

        $this->assertEquals(1, $line->getTenantId());
        $this->assertEquals(100, $line->getSalesOrderId());
        $this->assertEquals(5, $line->getProductId());
        $this->assertEquals(10.0, $line->getQuantity());
        $this->assertEquals(25.0, $line->getUnitPrice());
        $this->assertEquals('pending', $line->getStatus());
        $this->assertNull($line->getId());
        $this->assertNull($line->getProductVariantId());
        $this->assertNull($line->getBatchNumber());
        $this->assertNull($line->getSerialNumber());
    }

    public function test_sales_order_line_start_picking(): void
    {
        $line = new SalesOrderLine(tenantId: 1, salesOrderId: 100, productId: 5, quantity: 10.0, unitPrice: 25.0);
        $line->startPicking();

        $this->assertEquals('picking', $line->getStatus());
    }

    public function test_sales_order_line_pack(): void
    {
        $line = new SalesOrderLine(tenantId: 1, salesOrderId: 100, productId: 5, quantity: 10.0, unitPrice: 25.0);
        $line->startPicking();
        $line->pack();

        $this->assertEquals('packed', $line->getStatus());
    }

    public function test_sales_order_line_dispatch(): void
    {
        $line = new SalesOrderLine(tenantId: 1, salesOrderId: 100, productId: 5, quantity: 10.0, unitPrice: 25.0);
        $line->dispatch();

        $this->assertEquals('dispatched', $line->getStatus());
    }

    public function test_sales_order_line_cancel(): void
    {
        $line = new SalesOrderLine(tenantId: 1, salesOrderId: 100, productId: 5, quantity: 10.0, unitPrice: 25.0);
        $line->cancel();

        $this->assertEquals('cancelled', $line->getStatus());
    }

    // ========================================================================
    // SALES ORDER — EVENTS
    // ========================================================================

    public function test_sales_order_created_event_extends_base_event(): void
    {
        $event = new SalesOrderCreated(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_confirmed_event_extends_base_event(): void
    {
        $event = new SalesOrderConfirmed(1, 42);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_updated_event_extends_base_event(): void
    {
        $event = new SalesOrderUpdated(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_deleted_event_extends_base_event(): void
    {
        $event = new SalesOrderDeleted(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_shipped_event_extends_base_event(): void
    {
        $event = new SalesOrderShipped(1, 7);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_picking_started_event_extends_base_event(): void
    {
        $event = new SalesOrderPickingStarted(salesOrderId: 1, tenantId: 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
        $this->assertSame(1, $event->salesOrderId);
    }

    public function test_sales_order_packing_started_event_extends_base_event(): void
    {
        $event = new SalesOrderPackingStarted(salesOrderId: 2, tenantId: 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
        $this->assertSame(2, $event->salesOrderId);
    }

    public function test_sales_order_picking_started_broadcast_with(): void
    {
        $event   = new SalesOrderPickingStarted(salesOrderId: 5, tenantId: 1);
        $payload = $event->broadcastWith();
        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(5, $payload['id']);
    }

    public function test_sales_order_packing_started_broadcast_with(): void
    {
        $event   = new SalesOrderPackingStarted(salesOrderId: 6, tenantId: 1);
        $payload = $event->broadcastWith();
        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(6, $payload['id']);
    }

    public function test_sales_order_delivered_event_extends_base_event(): void
    {
        $event = new SalesOrderDelivered(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_cancelled_event_extends_base_event(): void
    {
        $event = new SalesOrderCancelled(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_line_created_event_extends_base_event(): void
    {
        $event = new SalesOrderLineCreated(10, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_line_updated_event_extends_base_event(): void
    {
        $event = new SalesOrderLineUpdated(10, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_sales_order_line_deleted_event_extends_base_event(): void
    {
        $event = new SalesOrderLineDeleted(10, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    // ========================================================================
    // SALES ORDER — EXCEPTIONS
    // ========================================================================

    public function test_sales_order_not_found_exception(): void
    {
        $e = new SalesOrderNotFoundException(99);
        $this->assertStringContainsString('99', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    public function test_sales_order_line_not_found_exception(): void
    {
        $e = new SalesOrderLineNotFoundException(55);
        $this->assertStringContainsString('55', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    // ========================================================================
    // SALES ORDER — DTOs
    // ========================================================================

    public function test_sales_order_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(SalesOrderData::class, BaseDto::class, true));
    }

    public function test_update_sales_order_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(UpdateSalesOrderData::class, BaseDto::class, true));
    }

    public function test_sales_order_line_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(SalesOrderLineData::class, BaseDto::class, true));
    }

    public function test_update_sales_order_line_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(UpdateSalesOrderLineData::class, BaseDto::class, true));
    }

    // ========================================================================
    // SALES ORDER — SERVICE INTERFACES
    // ========================================================================

    public function test_create_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(CreateSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_sales_order_service_interface_is_read_service(): void
    {
        $this->assertTrue(is_a(FindSalesOrderServiceInterface::class, ReadServiceInterface::class, true));
    }

    public function test_update_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(UpdateSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_delete_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(DeleteSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_confirm_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(ConfirmSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_cancel_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(CancelSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_start_picking_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(StartPickingSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_start_packing_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(StartPackingSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_ship_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(ShipSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_deliver_sales_order_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(DeliverSalesOrderServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_create_sales_order_line_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(CreateSalesOrderLineServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_sales_order_line_service_interface_is_read_service(): void
    {
        $this->assertTrue(is_a(FindSalesOrderLineServiceInterface::class, ReadServiceInterface::class, true));
    }

    // ========================================================================
    // SALES ORDER — SERVICES EXTEND BASE SERVICE
    // ========================================================================

    public function test_create_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CreateSalesOrderService::class, BaseService::class, true));
    }

    public function test_confirm_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(ConfirmSalesOrderService::class, BaseService::class, true));
    }

    public function test_cancel_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CancelSalesOrderService::class, BaseService::class, true));
    }

    public function test_start_picking_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(StartPickingSalesOrderService::class, BaseService::class, true));
    }

    public function test_start_packing_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(StartPackingSalesOrderService::class, BaseService::class, true));
    }

    public function test_ship_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(ShipSalesOrderService::class, BaseService::class, true));
    }

    public function test_deliver_sales_order_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(DeliverSalesOrderService::class, BaseService::class, true));
    }

    // ========================================================================
    // SALES ORDER — INFRASTRUCTURE
    // ========================================================================

    public function test_sales_order_model_table(): void
    {
        $model = new SalesOrderModel;
        $this->assertEquals('sales_orders', $model->getTable());
    }

    public function test_sales_order_line_model_table(): void
    {
        $model = new SalesOrderLineModel;
        $this->assertEquals('sales_order_lines', $model->getTable());
    }

    public function test_sales_order_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentSalesOrderRepository::class,
            SalesOrderRepositoryInterface::class,
            true
        ));
    }

    public function test_sales_order_line_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentSalesOrderLineRepository::class,
            SalesOrderLineRepositoryInterface::class,
            true
        ));
    }

    public function test_sales_order_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderController::class));
    }

    public function test_sales_order_line_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderLineController::class));
    }

    public function test_store_sales_order_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreSalesOrderRequest::class));
    }

    public function test_update_sales_order_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateSalesOrderRequest::class));
    }

    public function test_store_sales_order_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreSalesOrderLineRequest::class));
    }

    public function test_update_sales_order_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateSalesOrderLineRequest::class));
    }

    public function test_sales_order_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderResource::class));
    }

    public function test_sales_order_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderCollection::class));
    }

    public function test_sales_order_line_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderLineResource::class));
    }

    public function test_sales_order_line_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderLineCollection::class));
    }

    public function test_sales_order_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(SalesOrderServiceProvider::class));
    }

    // ========================================================================
    // DISPATCH — VALUE OBJECTS
    // ========================================================================

    public function test_dispatch_status_constants(): void
    {
        $this->assertSame('draft', DispatchStatus::DRAFT);
        $this->assertSame('confirmed', DispatchStatus::CONFIRMED);
        $this->assertSame('in_transit', DispatchStatus::IN_TRANSIT);
        $this->assertSame('delivered', DispatchStatus::DELIVERED);
        $this->assertSame('cancelled', DispatchStatus::CANCELLED);
    }

    public function test_dispatch_status_values(): void
    {
        $values = DispatchStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('confirmed', $values);
        $this->assertContains('in_transit', $values);
        $this->assertContains('delivered', $values);
        $this->assertContains('cancelled', $values);
        $this->assertCount(5, $values);
    }

    public function test_dispatch_line_status_constants(): void
    {
        $this->assertSame('pending', DispatchLineStatus::PENDING);
        $this->assertSame('cancelled', DispatchLineStatus::CANCELLED);
    }

    public function test_dispatch_line_status_values(): void
    {
        $values = DispatchLineStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('cancelled', $values);
        $this->assertIsArray($values);
    }

    // ========================================================================
    // DISPATCH — ENTITY
    // ========================================================================

    public function test_dispatch_entity_defaults(): void
    {
        $dispatch = new Dispatch(
            tenantId: 1,
            referenceNumber: 'DISP-001',
            warehouseId: 2,
            customerId: 10,
            dispatchDate: '2026-04-01',
        );

        $this->assertEquals(1, $dispatch->getTenantId());
        $this->assertEquals('DISP-001', $dispatch->getReferenceNumber());
        $this->assertEquals(2, $dispatch->getWarehouseId());
        $this->assertEquals(10, $dispatch->getCustomerId());
        $this->assertEquals('2026-04-01', $dispatch->getDispatchDate());
        $this->assertEquals('draft', $dispatch->getStatus());
        $this->assertTrue($dispatch->isDraft());
        $this->assertFalse($dispatch->isConfirmed());
        $this->assertFalse($dispatch->isCancelled());
        $this->assertNull($dispatch->getId());
        $this->assertNull($dispatch->getSalesOrderId());
        $this->assertNull($dispatch->getCustomerReference());
        $this->assertNull($dispatch->getCarrier());
        $this->assertNull($dispatch->getTrackingNumber());
        $this->assertNull($dispatch->getEstimatedDeliveryDate());
        $this->assertNull($dispatch->getActualDeliveryDate());
    }

    public function test_dispatch_entity_confirm(): void
    {
        $dispatch = new Dispatch(tenantId: 1, referenceNumber: 'DISP-001', warehouseId: 2, customerId: 10, dispatchDate: '2026-04-01');
        $dispatch->confirm(5);

        $this->assertEquals('confirmed', $dispatch->getStatus());
        $this->assertTrue($dispatch->isConfirmed());
        $this->assertEquals(5, $dispatch->getConfirmedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $dispatch->getConfirmedAt());
    }

    public function test_dispatch_entity_ship(): void
    {
        $dispatch = new Dispatch(tenantId: 1, referenceNumber: 'DISP-001', warehouseId: 2, customerId: 10, dispatchDate: '2026-04-01');
        $dispatch->ship(3, 'TRACK-123');

        $this->assertEquals('in_transit', $dispatch->getStatus());
        $this->assertEquals(3, $dispatch->getShippedBy());
        $this->assertEquals('TRACK-123', $dispatch->getTrackingNumber());
        $this->assertInstanceOf(\DateTimeInterface::class, $dispatch->getShippedAt());
    }

    public function test_dispatch_entity_deliver(): void
    {
        $dispatch = new Dispatch(tenantId: 1, referenceNumber: 'DISP-001', warehouseId: 2, customerId: 10, dispatchDate: '2026-04-01');
        $dispatch->ship(3);
        $dispatch->deliver('2026-04-05');

        $this->assertEquals('delivered', $dispatch->getStatus());
        $this->assertEquals('2026-04-05', $dispatch->getActualDeliveryDate());
    }

    public function test_dispatch_entity_cancel(): void
    {
        $dispatch = new Dispatch(tenantId: 1, referenceNumber: 'DISP-001', warehouseId: 2, customerId: 10, dispatchDate: '2026-04-01');
        $dispatch->cancel();

        $this->assertEquals('cancelled', $dispatch->getStatus());
        $this->assertTrue($dispatch->isCancelled());
    }

    public function test_dispatch_entity_update_details(): void
    {
        $dispatch = new Dispatch(tenantId: 1, referenceNumber: 'DISP-001', warehouseId: 2, customerId: 10, dispatchDate: '2026-04-01');
        $dispatch->updateDetails(
            customerReference: 'CUST-REF-001',
            estimatedDeliveryDate: '2026-04-10',
            carrier: 'FedEx',
            trackingNumber: null,
            notes: 'Fragile items',
            metadataArray: ['priority' => 'express'],
            totalWeight: 15.5,
        );

        $this->assertEquals('CUST-REF-001', $dispatch->getCustomerReference());
        $this->assertEquals('2026-04-10', $dispatch->getEstimatedDeliveryDate());
        $this->assertEquals('FedEx', $dispatch->getCarrier());
        $this->assertEquals('Fragile items', $dispatch->getNotes());
    }

    public function test_dispatch_timestamps_set_by_default(): void
    {
        $dispatch = new Dispatch(tenantId: 1, referenceNumber: 'DISP-001', warehouseId: 2, customerId: 10, dispatchDate: '2026-04-01');

        $this->assertInstanceOf(\DateTimeInterface::class, $dispatch->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $dispatch->getUpdatedAt());
    }

    // ========================================================================
    // DISPATCH LINE — ENTITY
    // ========================================================================

    public function test_dispatch_line_entity_defaults(): void
    {
        $line = new DispatchLine(
            tenantId: 1,
            dispatchId: 100,
            productId: 5,
            quantity: 3.0,
        );

        $this->assertEquals(1, $line->getTenantId());
        $this->assertEquals(100, $line->getDispatchId());
        $this->assertEquals(5, $line->getProductId());
        $this->assertEquals(3.0, $line->getQuantity());
        $this->assertEquals('pending', $line->getStatus());
        $this->assertNull($line->getId());
        $this->assertNull($line->getSalesOrderLineId());
        $this->assertNull($line->getBatchNumber());
        $this->assertNull($line->getSerialNumber());
    }

    public function test_dispatch_line_pick(): void
    {
        $line = new DispatchLine(tenantId: 1, dispatchId: 100, productId: 5, quantity: 3.0);
        $line->pick();

        $this->assertEquals('picked', $line->getStatus());
    }

    public function test_dispatch_line_pack(): void
    {
        $line = new DispatchLine(tenantId: 1, dispatchId: 100, productId: 5, quantity: 3.0);
        $line->pick();
        $line->pack();

        $this->assertEquals('packed', $line->getStatus());
    }

    public function test_dispatch_line_ship(): void
    {
        $line = new DispatchLine(tenantId: 1, dispatchId: 100, productId: 5, quantity: 3.0);
        $line->ship();

        $this->assertEquals('shipped', $line->getStatus());
    }

    public function test_dispatch_line_cancel(): void
    {
        $line = new DispatchLine(tenantId: 1, dispatchId: 100, productId: 5, quantity: 3.0);
        $line->cancel();

        $this->assertEquals('cancelled', $line->getStatus());
    }

    // ========================================================================
    // DISPATCH — EVENTS
    // ========================================================================

    public function test_dispatch_created_event_extends_base_event(): void
    {
        $event = new DispatchCreated(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_confirmed_event_extends_base_event(): void
    {
        $event = new DispatchConfirmed(1, 5);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_updated_event_extends_base_event(): void
    {
        $event = new DispatchUpdated(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_deleted_event_extends_base_event(): void
    {
        $event = new DispatchDeleted(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_shipped_event_extends_base_event(): void
    {
        $event = new DispatchShipped(1, 3);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_delivered_event_extends_base_event(): void
    {
        $event = new DispatchDelivered(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_cancelled_event_extends_base_event(): void
    {
        $event = new DispatchCancelled(1, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_line_created_event_extends_base_event(): void
    {
        $event = new DispatchLineCreated(10, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_line_updated_event_extends_base_event(): void
    {
        $event = new DispatchLineUpdated(10, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_dispatch_line_deleted_event_extends_base_event(): void
    {
        $event = new DispatchLineDeleted(10, 1);
        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    // ========================================================================
    // DISPATCH — EXCEPTIONS
    // ========================================================================

    public function test_dispatch_not_found_exception(): void
    {
        $e = new DispatchNotFoundException(77);
        $this->assertStringContainsString('77', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    public function test_dispatch_line_not_found_exception(): void
    {
        $e = new DispatchLineNotFoundException(88);
        $this->assertStringContainsString('88', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    // ========================================================================
    // DISPATCH — DTOs
    // ========================================================================

    public function test_dispatch_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(DispatchData::class, BaseDto::class, true));
    }

    public function test_update_dispatch_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(UpdateDispatchData::class, BaseDto::class, true));
    }

    public function test_dispatch_line_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(DispatchLineData::class, BaseDto::class, true));
    }

    public function test_update_dispatch_line_data_extends_base_dto(): void
    {
        $this->assertTrue(is_a(UpdateDispatchLineData::class, BaseDto::class, true));
    }

    // ========================================================================
    // DISPATCH — SERVICE INTERFACES
    // ========================================================================

    public function test_create_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(CreateDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_dispatch_service_interface_is_read_service(): void
    {
        $this->assertTrue(is_a(FindDispatchServiceInterface::class, ReadServiceInterface::class, true));
    }

    public function test_update_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(UpdateDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_delete_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(DeleteDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_confirm_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(ConfirmDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_cancel_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(CancelDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_ship_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(ShipDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_deliver_dispatch_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(DeliverDispatchServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_create_dispatch_line_service_interface_is_write_service(): void
    {
        $this->assertTrue(is_a(CreateDispatchLineServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_dispatch_line_service_interface_is_read_service(): void
    {
        $this->assertTrue(is_a(FindDispatchLineServiceInterface::class, ReadServiceInterface::class, true));
    }

    // ========================================================================
    // DISPATCH — SERVICES EXTEND BASE SERVICE
    // ========================================================================

    public function test_create_dispatch_service_extends_base_service(): void
    {
        $this->assertTrue(is_a(CreateDispatchService::class, BaseService::class, true));
    }

    // ========================================================================
    // DISPATCH — INFRASTRUCTURE
    // ========================================================================

    public function test_dispatch_model_table(): void
    {
        $model = new DispatchModel;
        $this->assertEquals('dispatches', $model->getTable());
    }

    public function test_dispatch_line_model_table(): void
    {
        $model = new DispatchLineModel;
        $this->assertEquals('dispatch_lines', $model->getTable());
    }

    public function test_dispatch_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentDispatchRepository::class,
            DispatchRepositoryInterface::class,
            true
        ));
    }

    public function test_dispatch_line_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(
            EloquentDispatchLineRepository::class,
            DispatchLineRepositoryInterface::class,
            true
        ));
    }

    public function test_dispatch_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchController::class));
    }

    public function test_dispatch_line_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchLineController::class));
    }

    public function test_store_dispatch_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreDispatchRequest::class));
    }

    public function test_update_dispatch_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateDispatchRequest::class));
    }

    public function test_store_dispatch_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreDispatchLineRequest::class));
    }

    public function test_update_dispatch_line_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateDispatchLineRequest::class));
    }

    public function test_dispatch_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchResource::class));
    }

    public function test_dispatch_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchCollection::class));
    }

    public function test_dispatch_line_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchLineResource::class));
    }

    public function test_dispatch_line_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchLineCollection::class));
    }

    public function test_dispatch_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(DispatchServiceProvider::class));
    }

    // ========================================================================
    // SALES ORDER — PICKING & PACKING ROUTES
    // ========================================================================

    public function test_sales_order_routes_contain_start_picking(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/SalesOrder/routes/api.php'
        );
        $this->assertStringContainsString('start-picking', $routes);
    }

    public function test_sales_order_routes_contain_start_packing(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/SalesOrder/routes/api.php'
        );
        $this->assertStringContainsString('start-packing', $routes);
    }

    public function test_start_picking_sales_order_service_class_exists(): void
    {
        $this->assertTrue(class_exists(StartPickingSalesOrderService::class));
    }

    public function test_start_packing_sales_order_service_class_exists(): void
    {
        $this->assertTrue(class_exists(StartPackingSalesOrderService::class));
    }

    public function test_start_picking_sales_order_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(StartPickingSalesOrderService::class, StartPickingSalesOrderServiceInterface::class)
        );
    }

    public function test_start_packing_sales_order_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(StartPackingSalesOrderService::class, StartPackingSalesOrderServiceInterface::class)
        );
    }

    public function test_start_picking_sales_order_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(SalesOrderRepositoryInterface::class);
        $service = new StartPickingSalesOrderService($repo);
        $this->assertInstanceOf(StartPickingSalesOrderService::class, $service);
    }

    public function test_start_packing_sales_order_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(SalesOrderRepositoryInterface::class);
        $service = new StartPackingSalesOrderService($repo);
        $this->assertInstanceOf(StartPackingSalesOrderService::class, $service);
    }
}
