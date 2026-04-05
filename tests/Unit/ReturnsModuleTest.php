<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Returns\Domain\Entities\ReturnRequest;
use Modules\Returns\Domain\Entities\ReturnLine;

class ReturnsModuleTest extends TestCase
{
    private function makeReturn(string $status = 'pending'): ReturnRequest
    {
        return new ReturnRequest(
            1, 1, 'purchase', 10, 'RET-001', $status,
            'Damaged goods', null, null, [], null, null, null,
            ReturnRequest::RETURN_TO_WAREHOUSE, 0.0, null, null, null,
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // ReturnRequest entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_return_request_creation(): void
    {
        $ret = $this->makeReturn();
        $this->assertEquals('RET-001', $ret->getReturnNumber());
        $this->assertEquals('pending', $ret->getStatus());
        $this->assertEquals('purchase', $ret->getReturnType());
        $this->assertEquals(ReturnRequest::RETURN_TO_WAREHOUSE, $ret->getReturnTo());
        $this->assertEquals(0.0, $ret->getRestockingFee());
    }

    public function test_approve_return(): void
    {
        $ret = $this->makeReturn();
        $ret->approve(5);
        $this->assertEquals('approved', $ret->getStatus());
        $this->assertEquals(5, $ret->getProcessedBy());
        $this->assertNotNull($ret->getProcessedAt());
    }

    public function test_reject_return(): void
    {
        $ret = $this->makeReturn();
        $ret->reject(5);
        $this->assertEquals('rejected', $ret->getStatus());
        $this->assertEquals(5, $ret->getProcessedBy());
        $this->assertNotNull($ret->getProcessedAt());
    }

    public function test_approve_fails_if_not_pending(): void
    {
        $ret = $this->makeReturn('approved');
        $this->expectException(\DomainException::class);
        $ret->approve(5);
    }

    public function test_reject_fails_if_not_pending(): void
    {
        $ret = $this->makeReturn('rejected');
        $this->expectException(\DomainException::class);
        $ret->reject(5);
    }

    public function test_start_restocking_requires_approved(): void
    {
        $ret = $this->makeReturn('pending');
        $this->expectException(\DomainException::class);
        $ret->startRestocking();
    }

    public function test_start_restocking_transitions_to_restocking(): void
    {
        $ret = $this->makeReturn('approved');
        $ret->startRestocking();
        $this->assertEquals(ReturnRequest::STATUS_RESTOCKING, $ret->getStatus());
    }

    public function test_complete_restock_requires_restocking_status(): void
    {
        $ret = $this->makeReturn('approved');
        $this->expectException(\DomainException::class);
        $ret->completeRestock(7);
    }

    public function test_complete_restock_transitions_correctly(): void
    {
        $ret = $this->makeReturn('approved');
        $ret->startRestocking();
        $ret->completeRestock(7, 99);
        $this->assertEquals(ReturnRequest::STATUS_RESTOCKED, $ret->getStatus());
        $this->assertEquals(7, $ret->getRestockedBy());
        $this->assertEquals(99, $ret->getCreditMemoId());
        $this->assertNotNull($ret->getRestockedAt());
    }

    public function test_complete_from_approved(): void
    {
        $ret = $this->makeReturn('approved');
        $ret->complete();
        $this->assertEquals('completed', $ret->getStatus());
    }

    public function test_complete_from_restocked(): void
    {
        $ret = $this->makeReturn('approved');
        $ret->startRestocking();
        $ret->completeRestock(7);
        $ret->complete();
        $this->assertEquals('completed', $ret->getStatus());
    }

    public function test_complete_fails_if_pending(): void
    {
        $ret = $this->makeReturn('pending');
        $this->expectException(\DomainException::class);
        $ret->complete();
    }

    // ──────────────────────────────────────────────────────────────────────
    // ReturnLine entity tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeReturnLine(
        string $condition    = ReturnLine::CONDITION_GOOD,
        string $qualityStatus = ReturnLine::QUALITY_PENDING,
    ): ReturnLine {
        return new ReturnLine(
            1, 1, 1, 5.0, 10.0,
            'BATCH-001', null, null, 'Broken', $condition, $qualityStatus, null, null,
        );
    }

    public function test_return_line_creation(): void
    {
        $line = $this->makeReturnLine();
        $this->assertEquals(5.0, $line->getQuantityReturned());
        $this->assertEquals(10.0, $line->getUnitPrice());
        $this->assertEquals(ReturnLine::CONDITION_GOOD, $line->getCondition());
        $this->assertEquals(ReturnLine::QUALITY_PENDING, $line->getQualityStatus());
        $this->assertTrue($line->isGoodCondition());
        $this->assertFalse($line->isDamaged());
        $this->assertTrue($line->isEligibleForRestock());
    }

    public function test_return_line_condition_damaged(): void
    {
        $line = $this->makeReturnLine(ReturnLine::CONDITION_DAMAGED);
        $this->assertTrue($line->isDamaged());
        $this->assertTrue($line->isEligibleForRestock());  // damaged is still eligible
    }

    public function test_return_line_condition_unsellable_not_eligible(): void
    {
        $line = $this->makeReturnLine(ReturnLine::CONDITION_UNSELLABLE);
        $this->assertTrue($line->isUnsellable());
        $this->assertFalse($line->isEligibleForRestock());
    }

    public function test_return_line_approve_quality(): void
    {
        $line = $this->makeReturnLine();
        $line->approveQuality();
        $this->assertEquals(ReturnLine::QUALITY_APPROVED, $line->getQualityStatus());
    }

    public function test_return_line_reject_quality(): void
    {
        $line = $this->makeReturnLine();
        $line->rejectQuality();
        $this->assertEquals(ReturnLine::QUALITY_REJECTED, $line->getQualityStatus());
    }

    public function test_return_line_quality_check_fails_if_not_pending(): void
    {
        $line = $this->makeReturnLine(ReturnLine::CONDITION_GOOD, ReturnLine::QUALITY_APPROVED);
        $this->expectException(\DomainException::class);
        $line->approveQuality();
    }

    public function test_return_line_restock_requires_quality_approved(): void
    {
        $line = $this->makeReturnLine();
        $this->expectException(\DomainException::class);
        $line->recordRestock(1, 5.0);
    }

    public function test_return_line_record_restock(): void
    {
        $line = $this->makeReturnLine();
        $line->approveQuality();
        $line->recordRestock(2, 5.0);
        $this->assertEquals(2, $line->getRestockedToWarehouseId());
        $this->assertEquals(5.0, $line->getRestockedQuantity());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Constants and type tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_return_request_constants(): void
    {
        $this->assertEquals('purchase_return', ReturnRequest::TYPE_PURCHASE);
        $this->assertEquals('sales_return',    ReturnRequest::TYPE_SALES);
        $this->assertEquals('warehouse',       ReturnRequest::RETURN_TO_WAREHOUSE);
        $this->assertEquals('vendor',          ReturnRequest::RETURN_TO_VENDOR);
    }

    public function test_return_line_condition_constants(): void
    {
        $this->assertEquals('good',       ReturnLine::CONDITION_GOOD);
        $this->assertEquals('damaged',    ReturnLine::CONDITION_DAMAGED);
        $this->assertEquals('unsellable', ReturnLine::CONDITION_UNSELLABLE);
    }

    public function test_return_line_quality_constants(): void
    {
        $this->assertEquals('pending',  ReturnLine::QUALITY_PENDING);
        $this->assertEquals('approved', ReturnLine::QUALITY_APPROVED);
        $this->assertEquals('rejected', ReturnLine::QUALITY_REJECTED);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ReturnRequest – restocking fee, credit memo, return-to-vendor
    // ──────────────────────────────────────────────────────────────────────

    public function test_return_request_with_restocking_fee(): void
    {
        $ret = new ReturnRequest(
            2, 1, ReturnRequest::TYPE_SALES, 5, 'RET-002', 'pending',
            'Customer regrets', null, null, [], null, null, null,
            ReturnRequest::RETURN_TO_WAREHOUSE, 15.0, null, null, null,
        );
        $this->assertEquals(15.0, $ret->getRestockingFee());
    }

    public function test_return_request_credit_memo_association(): void
    {
        $ret = new ReturnRequest(
            3, 1, ReturnRequest::TYPE_SALES, 5, 'RET-003', 'completed',
            'Wrong item', null, null, [], null, null, null,
            ReturnRequest::RETURN_TO_WAREHOUSE, 0.0, 42, null, null,
        );
        $this->assertEquals(42, $ret->getCreditMemoId());
    }

    public function test_return_request_return_to_vendor(): void
    {
        $ret = new ReturnRequest(
            4, 1, ReturnRequest::TYPE_PURCHASE, 10, 'RET-004', 'approved',
            'Wrong shipment', null, null, [], null, null, null,
            ReturnRequest::RETURN_TO_VENDOR, 0.0, null, null, null,
        );
        $this->assertEquals(ReturnRequest::RETURN_TO_VENDOR, $ret->getReturnTo());
    }

    public function test_return_request_restock_tracking(): void
    {
        $ret = $this->makeReturn('restocking');
        $ret->completeRestock(7, 99);
        $this->assertEquals(7, $ret->getRestockedBy());
        $this->assertNotNull($ret->getRestockedAt());
        $this->assertEquals(99, $ret->getCreditMemoId());
    }

    // ──────────────────────────────────────────────────────────────────────
    // ReturnLine – batch/lot/serial traceability
    // ──────────────────────────────────────────────────────────────────────

    public function test_return_line_with_batch_and_lot(): void
    {
        $line = new ReturnLine(
            10, 1, 5, 3.0, 100.0,
            'BATCH-2024', 'LOT-A', null,
            'Defective', ReturnLine::CONDITION_DAMAGED, ReturnLine::QUALITY_PENDING,
            null, null,
        );
        $this->assertEquals('BATCH-2024', $line->getBatchNumber());
        $this->assertEquals('LOT-A', $line->getLotNumber());
        $this->assertNull($line->getSerialNumber());
        $this->assertTrue($line->isDamaged());
    }

    public function test_return_line_with_serial_number(): void
    {
        $line = new ReturnLine(
            11, 1, 5, 1.0, 500.0,
            null, null, 'SN-123456',
            'Defective unit', ReturnLine::CONDITION_GOOD, ReturnLine::QUALITY_PENDING,
            null, null,
        );
        $this->assertEquals('SN-123456', $line->getSerialNumber());
        $this->assertTrue($line->isGoodCondition());
    }
}
