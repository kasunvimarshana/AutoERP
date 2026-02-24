<?php

namespace Tests\Unit\Inventory;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Inventory\Application\UseCases\CreateCycleCountUseCase;
use Modules\Inventory\Application\UseCases\PostCycleCountUseCase;
use Modules\Inventory\Application\UseCases\RecordCountedQtyUseCase;
use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\CycleCountCreated;
use Modules\Inventory\Domain\Events\CycleCountPosted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Inventory Cycle Count use cases.
 *
 * Covers:
 *  - CreateCycleCountUseCase: missing warehouse guard, successful creation + event
 *  - RecordCountedQtyUseCase: negative qty guard, not-found guard, wrong-status guard,
 *    new-line creation, existing-line update, draft→in_progress promotion
 *  - PostCycleCountUseCase: not-found guard, already-posted guard, cancelled guard,
 *    empty-lines guard, successful post with adjustment movements + event,
 *    zero-variance lines are skipped
 */
class CycleCountUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateCycleCountUseCase
    // -------------------------------------------------------------------------

    public function test_create_cycle_count_throws_when_warehouse_missing(): void
    {
        $repo = Mockery::mock(CycleCountRepositoryInterface::class);

        DB::shouldReceive('transaction')->never();
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateCycleCountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Warehouse is required for a cycle count.');
        $useCase->execute([
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => '',
        ]);
    }

    public function test_create_cycle_count_succeeds_and_dispatches_event(): void
    {
        $created = (object) [
            'id'           => 'cc-uuid-1',
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => 'wh-1',
            'reference'    => 'CC-2024-ABCDEF',
            'count_date'   => '2024-06-01',
            'status'       => 'draft',
        ];

        $repo = Mockery::mock(CycleCountRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['warehouse_id'] === 'wh-1' &&
                $d['status'] === 'draft' &&
                str_starts_with($d['reference'], 'CC-')
            ))
            ->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(CycleCountCreated::class));

        $useCase = new CreateCycleCountUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => 'wh-1',
            'count_date'   => '2024-06-01',
        ]);

        $this->assertSame('cc-uuid-1', $result->id);
        $this->assertSame('draft', $result->status);
    }

    // -------------------------------------------------------------------------
    // RecordCountedQtyUseCase
    // -------------------------------------------------------------------------

    public function test_record_counted_qty_throws_when_negative(): void
    {
        $repo = Mockery::mock(CycleCountRepositoryInterface::class);

        $useCase = new RecordCountedQtyUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Counted quantity cannot be negative.');
        $useCase->execute([
            'cycle_count_id' => 'cc-1',
            'product_id'     => 'prod-1',
            'counted_qty'    => '-5',
        ]);
    }

    public function test_record_counted_qty_throws_when_cycle_count_not_found(): void
    {
        $repo = Mockery::mock(CycleCountRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing')->andReturn(null);

        $useCase = new RecordCountedQtyUseCase($repo);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Cycle count not found.');
        $useCase->execute([
            'cycle_count_id' => 'missing',
            'product_id'     => 'prod-1',
            'counted_qty'    => '10',
        ]);
    }

    public function test_record_counted_qty_throws_when_count_is_posted(): void
    {
        $count = (object) ['id' => 'cc-1', 'tenant_id' => 't-1', 'status' => 'posted'];

        $repo = Mockery::mock(CycleCountRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('cc-1')->andReturn($count);

        $useCase = new RecordCountedQtyUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Counted quantities can only be recorded on draft or in-progress cycle counts.');
        $useCase->execute([
            'cycle_count_id' => 'cc-1',
            'product_id'     => 'prod-1',
            'counted_qty'    => '10',
        ]);
    }

    public function test_record_counted_qty_creates_new_line_and_promotes_to_in_progress(): void
    {
        $count = (object) [
            'id'           => 'cc-1',
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => 'wh-1',
            'status'       => 'draft',
        ];

        $line = (object) [
            'id'           => 'line-1',
            'cycle_count_id' => 'cc-1',
            'product_id'   => 'prod-1',
            'counted_qty'  => '50.00000000',
            'expected_qty' => '0.00000000',
        ];

        $repo = Mockery::mock(CycleCountRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('cc-1')->andReturn($count);
        $repo->shouldReceive('linesForCount')->with('cc-1')->andReturn([]);
        $repo->shouldReceive('createLine')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['product_id'] === 'prod-1' &&
                $d['counted_qty'] === '50.00000000'
            ))
            ->andReturn($line);
        $repo->shouldReceive('update')
            ->once()
            ->with('cc-1', ['status' => 'in_progress']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordCountedQtyUseCase($repo);
        $result  = $useCase->execute([
            'cycle_count_id' => 'cc-1',
            'product_id'     => 'prod-1',
            'counted_qty'    => '50',
        ]);

        $this->assertSame('line-1', $result->id);
        $this->assertSame('50.00000000', $result->counted_qty);
    }

    public function test_record_counted_qty_updates_existing_line(): void
    {
        $count = (object) [
            'id'           => 'cc-1',
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => 'wh-1',
            'status'       => 'in_progress',
        ];

        $existingLine = (object) ['id' => 'line-1', 'product_id' => 'prod-1', 'counted_qty' => '30.00000000'];
        $updatedLine  = (object) ['id' => 'line-1', 'product_id' => 'prod-1', 'counted_qty' => '75.00000000'];

        $repo = Mockery::mock(CycleCountRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('cc-1')->andReturn($count);
        $repo->shouldReceive('linesForCount')->with('cc-1')->andReturn([$existingLine]);
        $repo->shouldReceive('updateLine')
            ->once()
            ->with('line-1', Mockery::on(fn ($d) => $d['counted_qty'] === '75.00000000'))
            ->andReturn($updatedLine);
        $repo->shouldReceive('update')->never();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordCountedQtyUseCase($repo);
        $result  = $useCase->execute([
            'cycle_count_id' => 'cc-1',
            'product_id'     => 'prod-1',
            'counted_qty'    => '75',
        ]);

        $this->assertSame('75.00000000', $result->counted_qty);
    }

    // -------------------------------------------------------------------------
    // PostCycleCountUseCase
    // -------------------------------------------------------------------------

    public function test_post_cycle_count_throws_when_not_found(): void
    {
        $ccRepo  = Mockery::mock(CycleCountRepositoryInterface::class);
        $movRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $ccRepo->shouldReceive('findById')->with('missing')->andReturn(null);

        $useCase = new PostCycleCountUseCase($ccRepo, $movRepo);

        $this->expectException(ModelNotFoundException::class);
        $useCase->execute(['cycle_count_id' => 'missing']);
    }

    public function test_post_cycle_count_throws_when_already_posted(): void
    {
        $count   = (object) ['id' => 'cc-1', 'status' => 'posted'];
        $ccRepo  = Mockery::mock(CycleCountRepositoryInterface::class);
        $movRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $ccRepo->shouldReceive('findById')->with('cc-1')->andReturn($count);

        $useCase = new PostCycleCountUseCase($ccRepo, $movRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cycle count has already been posted.');
        $useCase->execute(['cycle_count_id' => 'cc-1']);
    }

    public function test_post_cycle_count_throws_when_cancelled(): void
    {
        $count   = (object) ['id' => 'cc-1', 'status' => 'cancelled'];
        $ccRepo  = Mockery::mock(CycleCountRepositoryInterface::class);
        $movRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $ccRepo->shouldReceive('findById')->with('cc-1')->andReturn($count);

        $useCase = new PostCycleCountUseCase($ccRepo, $movRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A cancelled cycle count cannot be posted.');
        $useCase->execute(['cycle_count_id' => 'cc-1']);
    }

    public function test_post_cycle_count_throws_when_no_lines(): void
    {
        $count   = (object) ['id' => 'cc-1', 'status' => 'in_progress'];
        $ccRepo  = Mockery::mock(CycleCountRepositoryInterface::class);
        $movRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $ccRepo->shouldReceive('findById')->with('cc-1')->andReturn($count);
        $ccRepo->shouldReceive('linesForCount')->with('cc-1')->andReturn([]);

        DB::shouldReceive('transaction')->never();

        $useCase = new PostCycleCountUseCase($ccRepo, $movRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot post a cycle count with no counted lines.');
        $useCase->execute(['cycle_count_id' => 'cc-1']);
    }

    public function test_post_cycle_count_skips_zero_variance_lines(): void
    {
        $count = (object) [
            'id'           => 'cc-1',
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => 'wh-1',
            'location_id'  => null,
            'status'       => 'in_progress',
            'reference'    => 'CC-2024-AAA',
        ];

        $line = (object) [
            'product_id'   => 'prod-1',
            'expected_qty' => '100.00000000',
            'counted_qty'  => '100.00000000',
        ];

        $posted = (object) array_merge((array) $count, ['status' => 'posted']);

        $ccRepo  = Mockery::mock(CycleCountRepositoryInterface::class);
        $movRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $ccRepo->shouldReceive('findById')->andReturn($count, $posted);
        $ccRepo->shouldReceive('linesForCount')->with('cc-1')->andReturn([$line]);
        $ccRepo->shouldReceive('update')->once()->with('cc-1', ['status' => 'posted']);
        $movRepo->shouldReceive('create')->never();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(CycleCountPosted::class));

        $useCase = new PostCycleCountUseCase($ccRepo, $movRepo);
        $result  = $useCase->execute(['cycle_count_id' => 'cc-1', 'posted_by' => 'user-1']);

        $this->assertSame('posted', $result->status);
    }

    public function test_post_cycle_count_creates_adjustment_movement_for_variance(): void
    {
        $count = (object) [
            'id'           => 'cc-1',
            'tenant_id'    => 'tenant-1',
            'warehouse_id' => 'wh-1',
            'location_id'  => 'loc-1',
            'status'       => 'in_progress',
            'reference'    => 'CC-2024-BBB',
        ];

        // counted 120 vs expected 100 → +20 variance → adjustment_in
        $line = (object) [
            'product_id'   => 'prod-1',
            'expected_qty' => '100.00000000',
            'counted_qty'  => '120.00000000',
        ];

        $posted = (object) array_merge((array) $count, ['status' => 'posted']);

        $ccRepo  = Mockery::mock(CycleCountRepositoryInterface::class);
        $movRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $ccRepo->shouldReceive('findById')->andReturn($count, $posted);
        $ccRepo->shouldReceive('linesForCount')->with('cc-1')->andReturn([$line]);
        $ccRepo->shouldReceive('update')->once()->with('cc-1', ['status' => 'posted']);
        $movRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['type'] === 'adjustment_in' &&
                $d['qty'] === '20.00000000' &&
                $d['reference_type'] === 'cycle_count'
            ));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(CycleCountPosted::class));

        $useCase = new PostCycleCountUseCase($ccRepo, $movRepo);
        $result  = $useCase->execute(['cycle_count_id' => 'cc-1', 'posted_by' => 'user-1']);

        $this->assertSame('posted', $result->status);
    }
}
