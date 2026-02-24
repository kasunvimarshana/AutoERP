<?php

namespace Tests\Unit\Inventory;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Inventory\Application\UseCases\GetInventoryValuationReportUseCase;
use Modules\Inventory\Application\UseCases\RecordValuationEntryUseCase;
use Modules\Inventory\Domain\Contracts\InventoryValuationRepositoryInterface;
use Modules\Inventory\Domain\Events\StockValuationEntryRecorded;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Inventory Stock Valuation use cases.
 *
 * Covers:
 * - Recording a receipt (qty + unit_cost → total_value, running balance)
 * - BCMath weighted-average cost recalculation
 * - Guard: zero quantity throws DomainException
 * - Guard: negative unit cost throws DomainException
 * - Deduction applies the running WAC and reduces the balance
 * - GetInventoryValuationReportUseCase delegates to the repository
 */
class InventoryValuationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEntry(
        string $qty   = '100.00000000',
        string $cost  = '10.00000000',
        string $total = '1000.00000000',
        string $balQty   = '100.00000000',
        string $balValue = '1000.00000000',
    ): object {
        return (object) [
            'id'                    => 'entry-uuid-1',
            'tenant_id'             => 'tenant-uuid-1',
            'product_id'            => 'prod-uuid-1',
            'movement_type'         => 'receipt',
            'qty'                   => $qty,
            'unit_cost'             => $cost,
            'total_value'           => $total,
            'running_balance_qty'   => $balQty,
            'running_balance_value' => $balValue,
            'valuation_method'      => 'weighted_average',
            'reference_type'        => null,
            'reference_id'          => null,
        ];
    }

    // -------------------------------------------------------------------------
    // RecordValuationEntryUseCase — receipt
    // -------------------------------------------------------------------------

    public function test_record_receipt_creates_entry_with_correct_bcmath_totals(): void
    {
        $repo = Mockery::mock(InventoryValuationRepositoryInterface::class);

        // No prior entry for this product
        $repo->shouldReceive('findLastByProduct')->once()->andReturn(null);

        // Capture what is passed to create()
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                // qty=50, unit_cost=20 → total_value = 50 * 20 = 1000
                return $data['movement_type'] === 'receipt'
                    && $data['qty'] === '50'
                    && $data['total_value'] === '1000.00000000'
                    && $data['running_balance_qty']   === '50.00000000'
                    && $data['running_balance_value'] === '1000.00000000';
            })
            ->andReturn($this->makeEntry('50.00000000', '20.00000000', '1000.00000000', '50.00000000', '1000.00000000'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof StockValuationEntryRecorded
                && $e->movementType === 'receipt'
                && $e->totalValue   === '1000.00000000');

        $useCase = new RecordValuationEntryUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'       => 'tenant-uuid-1',
            'product_id'      => 'prod-uuid-1',
            'movement_type'   => 'receipt',
            'qty'             => '50',
            'unit_cost'       => '20',
            'valuation_method' => 'weighted_average',
        ]);

        $this->assertSame('1000.00000000', $result->total_value);
        $this->assertSame('50.00000000',   $result->running_balance_qty);
    }

    public function test_weighted_average_recalculates_after_second_receipt(): void
    {
        $repo = Mockery::mock(InventoryValuationRepositoryInterface::class);

        // Existing balance: 100 units @ 10.00 = 1000.00
        $prior = $this->makeEntry(); // default = 100 qty, 1000 value
        $repo->shouldReceive('findLastByProduct')->once()->andReturn($prior);

        // Second receipt: 50 units @ 20.00 = 1000.00
        // New balance: 150 qty, 2000.00 value
        // WAC = 2000 / 150 = 13.33333333
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['movement_type']         === 'receipt'
                    && $data['running_balance_qty']   === '150.00000000'
                    && $data['running_balance_value'] === '2000.00000000';
            })
            ->andReturn($this->makeEntry('50.00000000', '13.33333333', '1000.00000000', '150.00000000', '2000.00000000'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new RecordValuationEntryUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'        => 'tenant-uuid-1',
            'product_id'       => 'prod-uuid-1',
            'movement_type'    => 'receipt',
            'qty'              => '50',
            'unit_cost'        => '20',
            'valuation_method' => 'weighted_average',
        ]);

        $this->assertSame('150.00000000', $result->running_balance_qty);
        $this->assertSame('2000.00000000', $result->running_balance_value);
    }

    // -------------------------------------------------------------------------
    // RecordValuationEntryUseCase — guard conditions
    // -------------------------------------------------------------------------

    public function test_zero_quantity_throws_domain_exception(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('quantity must be greater than zero');

        $repo = Mockery::mock(InventoryValuationRepositoryInterface::class);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordValuationEntryUseCase($repo);
        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'product_id'     => 'prod-uuid-1',
            'movement_type'  => 'receipt',
            'qty'            => '0',
            'unit_cost'      => '10',
        ]);
    }

    public function test_negative_unit_cost_throws_domain_exception(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unit cost cannot be negative');

        $repo = Mockery::mock(InventoryValuationRepositoryInterface::class);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordValuationEntryUseCase($repo);
        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'product_id'     => 'prod-uuid-1',
            'movement_type'  => 'receipt',
            'qty'            => '10',
            'unit_cost'      => '-5',
        ]);
    }

    // -------------------------------------------------------------------------
    // RecordValuationEntryUseCase — deduction
    // -------------------------------------------------------------------------

    public function test_deduction_uses_weighted_average_cost_and_reduces_balance(): void
    {
        $repo = Mockery::mock(InventoryValuationRepositoryInterface::class);

        // Existing: 100 units @ 10.00 = 1000.00
        $prior = $this->makeEntry();
        $repo->shouldReceive('findLastByProduct')->once()->andReturn($prior);

        // Deduct 30 units → WAC = 1000/100 = 10, total_value = -300.00000000
        // New balance: 70 units, 700.00
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['movement_type']         === 'deduction'
                    && $data['total_value']           === '-300.00000000'
                    && $data['running_balance_qty']   === '70.00000000'
                    && $data['running_balance_value'] === '700.00000000';
            })
            ->andReturn($this->makeEntry('30.00000000', '10.00000000', '-300.00000000', '70.00000000', '700.00000000'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof StockValuationEntryRecorded
                && $e->movementType === 'deduction'
                && $e->totalValue   === '-300.00000000');

        $useCase = new RecordValuationEntryUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'        => 'tenant-uuid-1',
            'product_id'       => 'prod-uuid-1',
            'movement_type'    => 'deduction',
            'qty'              => '30',
            'unit_cost'        => '10',
            'valuation_method' => 'weighted_average',
        ]);

        $this->assertSame('70.00000000',   $result->running_balance_qty);
        $this->assertSame('700.00000000',  $result->running_balance_value);
        $this->assertSame('-300.00000000', $result->total_value);
    }

    // -------------------------------------------------------------------------
    // GetInventoryValuationReportUseCase
    // -------------------------------------------------------------------------

    public function test_valuation_report_delegates_to_repository(): void
    {
        $repo = Mockery::mock(InventoryValuationRepositoryInterface::class);
        $repo->shouldReceive('valuationReport')
            ->once()
            ->with('tenant-uuid-1')
            ->andReturn([
                (object) ['product_id' => 'prod-1', 'total_qty' => '100.00000000', 'total_value' => '1000.00000000'],
                (object) ['product_id' => 'prod-2', 'total_qty' => '50.00000000',  'total_value' => '750.00000000'],
            ]);

        $useCase = new GetInventoryValuationReportUseCase($repo);
        $report  = $useCase->execute('tenant-uuid-1');

        $this->assertCount(2, $report);
        $this->assertSame('prod-1', $report[0]->product_id);
        $this->assertSame('1000.00000000', $report[0]->total_value);
    }
}
