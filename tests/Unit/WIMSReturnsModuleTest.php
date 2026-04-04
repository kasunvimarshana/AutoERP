<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Returns\Domain\ValueObjects\ReturnType;
use Modules\Returns\Domain\ValueObjects\ReturnStatus;
use Modules\Returns\Domain\ValueObjects\ReturnCondition;
use Modules\Returns\Domain\ValueObjects\QualityCheckResult;
use Modules\Returns\Domain\ValueObjects\CreditMemoStatus;
use Modules\Returns\Domain\ValueObjects\RmaStatus;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Entities\StockReturnLine;

class WIMSReturnsModuleTest extends TestCase
{
    // --------------- ReturnType VO ---------------

    public function test_return_type_purchase_return(): void
    {
        $this->assertSame('purchase_return', ReturnType::PURCHASE_RETURN);
    }

    public function test_return_type_sales_return(): void
    {
        $this->assertSame('sales_return', ReturnType::SALES_RETURN);
    }

    public function test_return_type_from_purchase(): void
    {
        $vo = ReturnType::from(ReturnType::PURCHASE_RETURN);
        $this->assertSame('purchase_return', (string) $vo);
    }

    public function test_return_type_from_sales(): void
    {
        $vo = ReturnType::from(ReturnType::SALES_RETURN);
        $this->assertSame('sales_return', (string) $vo);
    }

    public function test_return_type_from_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReturnType::from('garbage');
    }

    public function test_return_type_valid_returns_true_for_known(): void
    {
        $this->assertTrue(ReturnType::valid(ReturnType::SALES_RETURN));
    }

    public function test_return_type_valid_returns_false_for_unknown(): void
    {
        $this->assertFalse(ReturnType::valid('unknown'));
    }

    // --------------- ReturnStatus VO ---------------

    public function test_return_status_draft(): void
    {
        $vo = ReturnStatus::from(ReturnStatus::DRAFT);
        $this->assertSame('draft', (string) $vo);
    }

    public function test_return_status_pending(): void
    {
        $vo = ReturnStatus::from(ReturnStatus::PENDING);
        $this->assertSame('pending', (string) $vo);
    }

    public function test_return_status_approved(): void
    {
        $vo = ReturnStatus::from(ReturnStatus::APPROVED);
        $this->assertSame('approved', (string) $vo);
    }

    public function test_return_status_in_process(): void
    {
        $vo = ReturnStatus::from(ReturnStatus::IN_PROCESS);
        $this->assertSame('in_process', (string) $vo);
    }

    public function test_return_status_completed(): void
    {
        $vo = ReturnStatus::from(ReturnStatus::COMPLETED);
        $this->assertSame('completed', (string) $vo);
    }

    public function test_return_status_cancelled(): void
    {
        $vo = ReturnStatus::from(ReturnStatus::CANCELLED);
        $this->assertSame('cancelled', (string) $vo);
    }

    public function test_return_status_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReturnStatus::from('no_such_status');
    }

    // --------------- ReturnCondition VO ---------------

    public function test_return_condition_good(): void
    {
        $vo = ReturnCondition::from(ReturnCondition::GOOD);
        $this->assertSame('good', (string) $vo);
    }

    public function test_return_condition_damaged(): void
    {
        $vo = ReturnCondition::from(ReturnCondition::DAMAGED);
        $this->assertSame('damaged', (string) $vo);
    }

    public function test_return_condition_expired(): void
    {
        $vo = ReturnCondition::from(ReturnCondition::EXPIRED);
        $this->assertSame('expired', (string) $vo);
    }

    public function test_return_condition_faulty(): void
    {
        $vo = ReturnCondition::from(ReturnCondition::FAULTY);
        $this->assertSame('faulty', (string) $vo);
    }

    public function test_return_condition_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReturnCondition::from('unknown_condition');
    }

    // --------------- QualityCheckResult VO ---------------

    public function test_quality_check_pass(): void
    {
        $vo = QualityCheckResult::from(QualityCheckResult::PASS);
        $this->assertSame('pass', (string) $vo);
    }

    public function test_quality_check_fail(): void
    {
        $vo = QualityCheckResult::from(QualityCheckResult::FAIL);
        $this->assertSame('fail', (string) $vo);
    }

    public function test_quality_check_pending(): void
    {
        $vo = QualityCheckResult::from(QualityCheckResult::PENDING);
        $this->assertSame('pending', (string) $vo);
    }

    public function test_quality_check_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QualityCheckResult::from('bad_result');
    }

    // --------------- CreditMemoStatus VO ---------------

    public function test_credit_memo_status_draft(): void
    {
        $vo = CreditMemoStatus::from(CreditMemoStatus::DRAFT);
        $this->assertSame('draft', (string) $vo);
    }

    public function test_credit_memo_status_issued(): void
    {
        $vo = CreditMemoStatus::from(CreditMemoStatus::ISSUED);
        $this->assertSame('issued', (string) $vo);
    }

    public function test_credit_memo_status_applied(): void
    {
        $vo = CreditMemoStatus::from(CreditMemoStatus::APPLIED);
        $this->assertSame('applied', (string) $vo);
    }

    public function test_credit_memo_status_voided(): void
    {
        $vo = CreditMemoStatus::from(CreditMemoStatus::VOIDED);
        $this->assertSame('voided', (string) $vo);
    }

    public function test_credit_memo_status_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CreditMemoStatus::from('invalid_memo');
    }

    // --------------- RmaStatus VO ---------------

    public function test_rma_status_pending(): void
    {
        $vo = RmaStatus::from(RmaStatus::PENDING);
        $this->assertSame('pending', (string) $vo);
    }

    public function test_rma_status_approved(): void
    {
        $vo = RmaStatus::from(RmaStatus::APPROVED);
        $this->assertSame('approved', (string) $vo);
    }

    public function test_rma_status_expired(): void
    {
        $vo = RmaStatus::from(RmaStatus::EXPIRED);
        $this->assertSame('expired', (string) $vo);
    }

    public function test_rma_status_cancelled(): void
    {
        $vo = RmaStatus::from(RmaStatus::CANCELLED);
        $this->assertSame('cancelled', (string) $vo);
    }

    public function test_rma_status_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RmaStatus::from('bad_rma');
    }

    // --------------- StockReturn entity construction ---------------

    public function test_stock_return_construction_stores_id(): void
    {
        $return = new StockReturn(
            id: 1,
            tenantId: 1,
            warehouseId: 2,
            returnNumber: 'RET-001',
            returnType: ReturnType::SALES_RETURN,
            status: ReturnStatus::DRAFT,
        );
        $this->assertSame(1, $return->id);
    }

    public function test_stock_return_construction_stores_return_type(): void
    {
        $return = new StockReturn(
            id: null,
            tenantId: 1,
            warehouseId: 2,
            returnNumber: 'RET-002',
            returnType: ReturnType::PURCHASE_RETURN,
            status: ReturnStatus::PENDING,
        );
        $this->assertSame(ReturnType::PURCHASE_RETURN, $return->returnType);
    }

    public function test_stock_return_construction_stores_status(): void
    {
        $return = new StockReturn(
            id: null,
            tenantId: 1,
            warehouseId: 1,
            returnNumber: 'RET-003',
            returnType: ReturnType::SALES_RETURN,
            status: ReturnStatus::APPROVED,
        );
        $this->assertSame(ReturnStatus::APPROVED, $return->status);
    }

    // --------------- StockReturnLine entity construction ---------------

    public function test_stock_return_line_construction_stores_id(): void
    {
        $line = new StockReturnLine(
            id: 10,
            stockReturnId: 1,
            productId: 5,
            returnQty: 3.0,
            condition: ReturnCondition::GOOD,
            qualityCheckResult: QualityCheckResult::PASS,
            locationId: 2,
        );
        $this->assertSame(10, $line->id);
    }

    public function test_stock_return_line_construction_stores_qty(): void
    {
        $line = new StockReturnLine(
            id: null,
            stockReturnId: 1,
            productId: 5,
            returnQty: 7.5,
            condition: ReturnCondition::DAMAGED,
            qualityCheckResult: QualityCheckResult::FAIL,
            locationId: 2,
        );
        $this->assertSame(7.5, $line->returnQty);
    }

    public function test_stock_return_line_defaults_are_null(): void
    {
        $line = new StockReturnLine(
            id: null,
            stockReturnId: 1,
            productId: 1,
            returnQty: 1.0,
            condition: ReturnCondition::GOOD,
            qualityCheckResult: QualityCheckResult::PENDING,
            locationId: 1,
        );
        $this->assertNull($line->unitPrice);
        $this->assertNull($line->notes);
    }
}
