<?php

namespace Tests\Unit\Inventory;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Inventory\Application\UseCases\BlockLotUseCase;
use Modules\Inventory\Application\UseCases\CreateLotUseCase;
use Modules\Inventory\Domain\Contracts\InventoryLotRepositoryInterface;
use Modules\Inventory\Domain\Events\LotBlocked;
use Modules\Inventory\Domain\Events\LotCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Inventory Lot & Serial Number Tracking use cases.
 *
 * Covers:
 *  - CreateLotUseCase: empty lot number guard, negative qty guard,
 *    duplicate lot number guard, successful creation + LotCreated event
 *  - BlockLotUseCase: not-found guard, already-blocked guard,
 *    successful block + LotBlocked event
 */
class InventoryLotUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateLotUseCase
    // -------------------------------------------------------------------------

    public function test_create_lot_throws_when_lot_number_is_empty(): void
    {
        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);

        DB::shouldReceive('transaction')->never();
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateLotUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Lot number must not be empty.');
        $useCase->execute([
            'lot_number'   => '   ',
            'product_id'   => 'prod-1',
            'tenant_id'    => 'tenant-1',
            'qty'          => '10',
            'tracking_type'=> 'lot',
        ]);
    }

    public function test_create_lot_throws_when_qty_is_not_positive(): void
    {
        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);

        DB::shouldReceive('transaction')->never();
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateLotUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Lot quantity must be greater than zero.');
        $useCase->execute([
            'lot_number'    => 'LOT-001',
            'product_id'    => 'prod-1',
            'tenant_id'     => 'tenant-1',
            'qty'           => '0',
            'tracking_type' => 'lot',
        ]);
    }

    public function test_create_lot_throws_when_lot_number_already_exists(): void
    {
        $existingLot = (object) ['id' => 'existing-id', 'lot_number' => 'LOT-001'];

        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);
        $repo->shouldReceive('findByLotNumber')
            ->with('tenant-1', 'prod-1', 'LOT-001')
            ->andReturn($existingLot);

        DB::shouldReceive('transaction')->never();
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateLotUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Lot number 'LOT-001' already exists for this product.");
        $useCase->execute([
            'lot_number'    => 'LOT-001',
            'product_id'    => 'prod-1',
            'tenant_id'     => 'tenant-1',
            'qty'           => '50',
            'tracking_type' => 'lot',
        ]);
    }

    public function test_create_lot_succeeds_and_dispatches_event(): void
    {
        $createdLot = (object) [
            'id'            => 'lot-uuid-1',
            'tenant_id'     => 'tenant-1',
            'product_id'    => 'prod-1',
            'lot_number'    => 'LOT-2024-001',
            'tracking_type' => 'lot',
            'qty'           => '100.00000000',
            'status'        => 'active',
        ];

        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);
        $repo->shouldReceive('findByLotNumber')
            ->with('tenant-1', 'prod-1', 'LOT-2024-001')
            ->andReturn(null);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['lot_number'] === 'LOT-2024-001' &&
                $d['status'] === 'active' &&
                $d['qty'] === '100.00000000' &&
                $d['tracking_type'] === 'lot'
            ))
            ->andReturn($createdLot);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(LotCreated::class));

        $useCase = new CreateLotUseCase($repo);
        $result = $useCase->execute([
            'lot_number'    => 'LOT-2024-001',
            'product_id'    => 'prod-1',
            'tenant_id'     => 'tenant-1',
            'qty'           => '100',
            'tracking_type' => 'lot',
        ]);

        $this->assertSame('lot-uuid-1', $result->id);
        $this->assertSame('active', $result->status);
        $this->assertSame('100.00000000', $result->qty);
    }

    // -------------------------------------------------------------------------
    // BlockLotUseCase
    // -------------------------------------------------------------------------

    public function test_block_lot_throws_when_lot_not_found(): void
    {
        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->never();
        Event::shouldReceive('dispatch')->never();

        $useCase = new BlockLotUseCase($repo);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Lot not found.');
        $useCase->execute(['lot_id' => 'missing-id']);
    }

    public function test_block_lot_throws_when_already_blocked(): void
    {
        $lot = (object) [
            'id'         => 'lot-uuid-1',
            'tenant_id'  => 'tenant-1',
            'product_id' => 'prod-1',
            'lot_number' => 'LOT-001',
            'status'     => 'blocked',
        ];

        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('lot-uuid-1')->andReturn($lot);

        DB::shouldReceive('transaction')->never();
        Event::shouldReceive('dispatch')->never();

        $useCase = new BlockLotUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Lot is already blocked.');
        $useCase->execute(['lot_id' => 'lot-uuid-1']);
    }

    public function test_block_lot_succeeds_and_dispatches_event(): void
    {
        $lot = (object) [
            'id'         => 'lot-uuid-1',
            'tenant_id'  => 'tenant-1',
            'product_id' => 'prod-1',
            'lot_number' => 'LOT-001',
            'status'     => 'active',
        ];

        $blocked = (object) array_merge((array) $lot, ['status' => 'blocked']);

        $repo = Mockery::mock(InventoryLotRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('lot-uuid-1')->andReturn($lot);
        $repo->shouldReceive('update')
            ->once()
            ->with('lot-uuid-1', ['status' => 'blocked'])
            ->andReturn($blocked);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(LotBlocked::class));

        $useCase = new BlockLotUseCase($repo);
        $result = $useCase->execute(['lot_id' => 'lot-uuid-1']);

        $this->assertSame('blocked', $result->status);
    }
}
