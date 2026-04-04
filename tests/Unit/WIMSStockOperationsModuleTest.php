<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptStatus;
use Modules\StockMovement\Domain\ValueObjects\MovementType;

class WIMSStockOperationsModuleTest extends TestCase
{
    // --------------- GoodsReceiptStatus constants ---------------

    public function test_gr_status_pending_value(): void
    {
        $this->assertSame('pending', GoodsReceiptStatus::PENDING);
    }

    public function test_gr_status_under_inspection_value(): void
    {
        $this->assertSame('under_inspection', GoodsReceiptStatus::UNDER_INSPECTION);
    }

    public function test_gr_status_inspected_value(): void
    {
        $this->assertSame('inspected', GoodsReceiptStatus::INSPECTED);
    }

    public function test_gr_status_put_away_value(): void
    {
        $this->assertSame('put_away', GoodsReceiptStatus::PUT_AWAY);
    }

    public function test_gr_status_completed_value(): void
    {
        $this->assertSame('completed', GoodsReceiptStatus::COMPLETED);
    }

    public function test_gr_status_cancelled_value(): void
    {
        $this->assertSame('cancelled', GoodsReceiptStatus::CANCELLED);
    }

    public function test_gr_status_from_valid(): void
    {
        $vo = GoodsReceiptStatus::from(GoodsReceiptStatus::PENDING);
        $this->assertSame('pending', (string) $vo);
    }

    public function test_gr_status_from_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GoodsReceiptStatus::from('nonexistent');
    }

    public function test_gr_status_valid_list_contains_all(): void
    {
        $valid = GoodsReceiptStatus::valid();
        $this->assertContains('pending', $valid);
        $this->assertContains('completed', $valid);
        $this->assertContains('cancelled', $valid);
    }

    // --------------- GoodsReceipt.inspect() ---------------

    private function makeGR(string $status = GoodsReceiptStatus::PENDING): GoodsReceipt
    {
        return new GoodsReceipt(
            id: null,
            tenantId: 1,
            warehouseId: 1,
            grNumber: 'GR-001',
            status: $status,
        );
    }

    public function test_inspect_from_pending_sets_inspected_status(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::PENDING);
        $gr->inspect(99);
        $this->assertSame(GoodsReceiptStatus::INSPECTED, $gr->status);
    }

    public function test_inspect_from_under_inspection_sets_inspected_status(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::UNDER_INSPECTION);
        $gr->inspect(99);
        $this->assertSame(GoodsReceiptStatus::INSPECTED, $gr->status);
    }

    public function test_inspect_sets_inspected_by(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::PENDING);
        $gr->inspect(42);
        $this->assertSame(42, $gr->inspectedBy);
    }

    public function test_inspect_sets_inspected_at(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::PENDING);
        $gr->inspect(1);
        $this->assertInstanceOf(\DateTimeImmutable::class, $gr->inspectedAt);
    }

    public function test_inspect_from_completed_throws(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::COMPLETED);
        $this->expectException(\DomainException::class);
        $gr->inspect(1);
    }

    public function test_inspect_from_cancelled_throws(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::CANCELLED);
        $this->expectException(\DomainException::class);
        $gr->inspect(1);
    }

    public function test_inspect_from_put_away_throws(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::PUT_AWAY);
        $this->expectException(\DomainException::class);
        $gr->inspect(1);
    }

    public function test_put_away_after_inspect_sets_status(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::INSPECTED);
        $gr->putAway(5);
        $this->assertSame(GoodsReceiptStatus::PUT_AWAY, $gr->status);
    }

    public function test_put_away_without_inspect_throws(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::PENDING);
        $this->expectException(\DomainException::class);
        $gr->putAway(5);
    }

    public function test_complete_after_put_away_sets_status(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::PUT_AWAY);
        $gr->complete();
        $this->assertSame(GoodsReceiptStatus::COMPLETED, $gr->status);
    }

    public function test_complete_without_put_away_throws(): void
    {
        $gr = $this->makeGR(GoodsReceiptStatus::INSPECTED);
        $this->expectException(\DomainException::class);
        $gr->complete();
    }

    // --------------- MovementType constants ---------------

    public function test_movement_type_receipt(): void
    {
        $this->assertSame('receipt', MovementType::RECEIPT);
    }

    public function test_movement_type_issue(): void
    {
        $this->assertSame('issue', MovementType::ISSUE);
    }

    public function test_movement_type_transfer_in(): void
    {
        $this->assertSame('transfer_in', MovementType::TRANSFER_IN);
    }

    public function test_movement_type_transfer_out(): void
    {
        $this->assertSame('transfer_out', MovementType::TRANSFER_OUT);
    }

    public function test_movement_type_adjustment(): void
    {
        $this->assertSame('adjustment', MovementType::ADJUSTMENT);
    }

    public function test_movement_type_return_in(): void
    {
        $this->assertSame('return_in', MovementType::RETURN_IN);
    }

    public function test_movement_type_return_out(): void
    {
        $this->assertSame('return_out', MovementType::RETURN_OUT);
    }

    public function test_movement_type_from_valid(): void
    {
        $vo = MovementType::from(MovementType::RECEIPT);
        $this->assertSame('receipt', (string) $vo);
    }

    public function test_movement_type_from_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MovementType::from('not_a_type');
    }

    public function test_movement_type_valid_list_not_empty(): void
    {
        $this->assertNotEmpty(MovementType::valid());
    }
}
