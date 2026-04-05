<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\StockMovement\Domain\Entities\StockMovement;

class GoodsReceiptDispatchTest extends TestCase
{
    private function makeGR(string $status = 'pending'): GoodsReceipt
    {
        return new GoodsReceipt(1, 1, 1, 1, 'GR-001', $status, null, 1, null, null, null, null, [], null, null);
    }
    private function makeDispatch(string $status = 'pending'): Dispatch
    {
        return new Dispatch(1, 1, 1, 1, 'DSP-001', $status, null, null, null, [], null, null, null, null);
    }
    private function makeMovement(string $type = 'receipt'): StockMovement
    {
        return new StockMovement(1, 1, 1, 1, null, null, $type, 10.0, 5.0, 'REF-001', null, 1, null, null, null);
    }

    public function test_goods_receipt_creation(): void
    {
        $gr = $this->makeGR();
        $this->assertEquals('GR-001', $gr->getGrNumber());
        $this->assertEquals('pending', $gr->getStatus());
        $this->assertEquals(1, $gr->getWarehouseId());
    }

    public function test_goods_receipt_inspect(): void
    {
        $gr = $this->makeGR('pending');
        $gr->inspect(5);
        $this->assertEquals('inspected', $gr->getStatus());
        $this->assertEquals(5, $gr->getInspectedBy());
        $this->assertNotNull($gr->getInspectedAt());
    }

    public function test_goods_receipt_put_away(): void
    {
        $gr = $this->makeGR('pending');
        $gr->inspect(5);
        $gr->putAway(6);
        $this->assertEquals('put_away', $gr->getStatus());
        $this->assertEquals(6, $gr->getPutAwayBy());
        $this->assertNotNull($gr->getPutAwayAt());
    }

    public function test_goods_receipt_put_away_requires_inspected(): void
    {
        $gr = $this->makeGR('pending');
        $this->expectException(\DomainException::class);
        $gr->putAway(6);
    }

    public function test_goods_receipt_cannot_inspect_cancelled(): void
    {
        $gr = $this->makeGR('cancelled');
        $this->expectException(\DomainException::class);
        $gr->inspect(5);
    }

    public function test_dispatch_creation(): void
    {
        $d = $this->makeDispatch();
        $this->assertEquals('DSP-001', $d->getDispatchNumber());
        $this->assertEquals('pending', $d->getStatus());
    }

    public function test_dispatch_ship(): void
    {
        $d = $this->makeDispatch('pending');
        $d->ship('FedEx', 'TRACK-001');
        $this->assertEquals('shipped', $d->getStatus());
        $this->assertEquals('FedEx', $d->getCarrier());
        $this->assertEquals('TRACK-001', $d->getTrackingNumber());
        $this->assertNotNull($d->getShippedAt());
    }

    public function test_dispatch_mark_delivered(): void
    {
        $d = $this->makeDispatch('pending');
        $d->ship('FedEx', 'TRACK-001');
        $d->markDelivered();
        $this->assertEquals('delivered', $d->getStatus());
        $this->assertNotNull($d->getDeliveredAt());
    }

    public function test_dispatch_ship_fails_if_not_pending(): void
    {
        $d = $this->makeDispatch('shipped');
        $this->expectException(\DomainException::class);
        $d->ship('UPS', 'TRACK-002');
    }

    public function test_dispatch_deliver_fails_if_not_shipped(): void
    {
        $d = $this->makeDispatch('pending');
        $this->expectException(\DomainException::class);
        $d->markDelivered();
    }

    public function test_stock_movement_creation(): void
    {
        $m = $this->makeMovement('receipt');
        $this->assertEquals('receipt', $m->getMovementType());
        $this->assertEquals(10.0, $m->getQuantity());
        $this->assertEquals(5.0, $m->getUnitCost());
        $this->assertEquals('REF-001', $m->getReference());
    }

    public function test_stock_movement_types(): void
    {
        $this->assertEquals('receipt', StockMovement::TYPE_RECEIPT);
        $this->assertEquals('issue', StockMovement::TYPE_ISSUE);
        $this->assertEquals('transfer', StockMovement::TYPE_TRANSFER);
        $this->assertEquals('adjustment', StockMovement::TYPE_ADJUSTMENT);
        $this->assertEquals('return', StockMovement::TYPE_RETURN);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GoodsReceipt – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_goods_receipt_status_constants(): void
    {
        $this->assertEquals('pending',          GoodsReceipt::STATUS_PENDING);
        $this->assertEquals('under_inspection', GoodsReceipt::STATUS_UNDER_INSPECTION);
        $this->assertEquals('inspected',        GoodsReceipt::STATUS_INSPECTED);
        $this->assertEquals('put_away',         GoodsReceipt::STATUS_PUT_AWAY);
        $this->assertEquals('cancelled',        GoodsReceipt::STATUS_CANCELLED);
    }

    public function test_goods_receipt_getters(): void
    {
        $gr = $this->makeGR();
        $this->assertEquals(1, $gr->getId());
        $this->assertEquals(1, $gr->getTenantId());
        $this->assertEquals(1, $gr->getPurchaseOrderId());
        $this->assertEquals(1, $gr->getWarehouseId());
        $this->assertEquals(1, $gr->getReceivedBy());
        $this->assertIsArray($gr->getLines());
        $this->assertNull($gr->getNotes());
    }

    public function test_goods_receipt_with_lines(): void
    {
        $lines = [['product_id' => 1, 'quantity' => 10.0, 'unit_cost' => 5.0]];
        $gr = new GoodsReceipt(2, 1, 1, 1, 'GR-002', 'pending', 'Fragile', 1, null, null, null, null, $lines, null, null);
        $this->assertCount(1, $gr->getLines());
        $this->assertEquals('Fragile', $gr->getNotes());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Dispatch – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_dispatch_getters(): void
    {
        $d = $this->makeDispatch();
        $this->assertEquals(1, $d->getId());
        $this->assertEquals(1, $d->getTenantId());
        $this->assertEquals(1, $d->getSalesOrderId());
        $this->assertEquals(1, $d->getWarehouseId());
        $this->assertNull($d->getCarrier());
        $this->assertNull($d->getShippingCost());
    }

    public function test_dispatch_with_shipping_cost(): void
    {
        $d = new Dispatch(1, 1, 1, 1, 'DSP-001', 'pending', 'FedEx', null, 25.50, [], null, null, null, null);
        $this->assertEquals(25.50, $d->getShippingCost());
        $this->assertEquals('FedEx', $d->getCarrier());
    }

    public function test_dispatch_delivered_has_timestamps(): void
    {
        $d = $this->makeDispatch('pending');
        $d->ship('DHL', 'DHL-12345');
        $d->markDelivered();
        $this->assertNotNull($d->getShippedAt());
        $this->assertNotNull($d->getDeliveredAt());
    }
}
