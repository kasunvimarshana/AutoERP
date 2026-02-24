<?php

namespace Tests\Unit\Manufacturing;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Manufacturing\Application\UseCases\CreateWorkOrderUseCase;
use Modules\Manufacturing\Application\UseCases\StartWorkOrderUseCase;
use Modules\Manufacturing\Application\UseCases\CompleteWorkOrderUseCase;
use Modules\Manufacturing\Domain\Contracts\BomLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\BomRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderRepositoryInterface;
use Modules\Manufacturing\Domain\Events\WorkOrderStarted;
use Modules\Manufacturing\Domain\Events\WorkOrderCompleted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Manufacturing use cases.
 *
 * Covers BOM validation guards, BCMath scrap-rate quantity calculation,
 * work-order status transitions, and domain event dispatch.
 */
class WorkOrderUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateWorkOrderUseCase tests
    // -------------------------------------------------------------------------

    private function makeActiveBom(string $id = 'bom-uuid-1'): object
    {
        return (object) [
            'id'     => $id,
            'status' => 'active',
        ];
    }

    private function makeBomLine(string $qty, string $scrapRate = '0.00'): object
    {
        return (object) [
            'component_product_id' => 'prod-uuid-1',
            'component_name'       => 'Steel Plate',
            'quantity'             => $qty,
            'scrap_rate'           => $scrapRate,
            'unit'                 => 'kg',
        ];
    }

    private function makeWorkOrder(string $id = 'wo-uuid-1', string $status = 'draft'): object
    {
        return (object) [
            'id'               => $id,
            'tenant_id'        => 'tenant-uuid-1',
            'status'           => $status,
            'quantity_planned' => '10.00000000',
        ];
    }

    private function makeCreateUseCase(object $bom, array $bomLines, object $workOrder): CreateWorkOrderUseCase
    {
        $bomRepo = Mockery::mock(BomRepositoryInterface::class);
        $bomRepo->shouldReceive('findById')->andReturn($bom);

        $bomLineRepo = Mockery::mock(BomLineRepositoryInterface::class);
        $bomLineRepo->shouldReceive('findByBom')->andReturn(new Collection($bomLines));

        $workOrderRepo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $workOrderRepo->shouldReceive('create')->andReturn($workOrder);

        $workOrderLineRepo = Mockery::mock(WorkOrderLineRepositoryInterface::class);
        $workOrderLineRepo->shouldReceive('create')->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('withTrashed')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        return new CreateWorkOrderUseCase($bomRepo, $bomLineRepo, $workOrderRepo, $workOrderLineRepo);
    }

    public function test_throws_when_bom_not_found(): void
    {
        $bomRepo = Mockery::mock(BomRepositoryInterface::class);
        $bomRepo->shouldReceive('findById')->andReturn(null);

        $bomLineRepo       = Mockery::mock(BomLineRepositoryInterface::class);
        $workOrderRepo     = Mockery::mock(WorkOrderRepositoryInterface::class);
        $workOrderLineRepo = Mockery::mock(WorkOrderLineRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateWorkOrderUseCase($bomRepo, $bomLineRepo, $workOrderRepo, $workOrderLineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found or not in active/i');

        $useCase->execute([
            'bom_id'           => 'missing-bom',
            'quantity_planned' => '10',
            'tenant_id'        => 'tenant-uuid-1',
        ]);
    }

    public function test_throws_when_bom_is_not_active(): void
    {
        $draftBom = (object) ['id' => 'bom-uuid-1', 'status' => 'draft'];

        $bomRepo = Mockery::mock(BomRepositoryInterface::class);
        $bomRepo->shouldReceive('findById')->andReturn($draftBom);

        $bomLineRepo       = Mockery::mock(BomLineRepositoryInterface::class);
        $workOrderRepo     = Mockery::mock(WorkOrderRepositoryInterface::class);
        $workOrderLineRepo = Mockery::mock(WorkOrderLineRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateWorkOrderUseCase($bomRepo, $bomLineRepo, $workOrderRepo, $workOrderLineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found or not in active/i');

        $useCase->execute([
            'bom_id'           => 'bom-uuid-1',
            'quantity_planned' => '10',
            'tenant_id'        => 'tenant-uuid-1',
        ]);
    }

    public function test_creates_work_order_with_correct_quantity_no_scrap(): void
    {
        // BOM line qty=5 per unit, WO qty_planned=2 → required=10 (no scrap)
        $bom       = $this->makeActiveBom();
        $bomLine   = $this->makeBomLine('5.00000000', '0.00');
        $workOrder = $this->makeWorkOrder();

        $workOrderLineRepo = Mockery::mock(WorkOrderLineRepositoryInterface::class);
        $workOrderLineRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // 5 * 2 * (1 + 0/100) = 10.00000000
                return $data['quantity_required'] === '10.00000000';
            })
            ->andReturn((object) []);

        $bomRepo = Mockery::mock(BomRepositoryInterface::class);
        $bomRepo->shouldReceive('findById')->andReturn($bom);

        $bomLineRepo = Mockery::mock(BomLineRepositoryInterface::class);
        $bomLineRepo->shouldReceive('findByBom')->andReturn(new Collection([$bomLine]));

        $workOrderRepo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $workOrderRepo->shouldReceive('create')->andReturn($workOrder);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('withTrashed')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $useCase = new CreateWorkOrderUseCase($bomRepo, $bomLineRepo, $workOrderRepo, $workOrderLineRepo);

        $result = $useCase->execute([
            'bom_id'           => 'bom-uuid-1',
            'quantity_planned' => '2',
            'tenant_id'        => 'tenant-uuid-1',
        ]);

        $this->assertSame('wo-uuid-1', $result->id);
    }

    public function test_creates_work_order_with_scrap_rate(): void
    {
        // BOM line qty=10 per unit, WO qty=1, scrap_rate=10% → required = 10 * 1 * 1.1 = 11.00000000
        $bom       = $this->makeActiveBom();
        $bomLine   = $this->makeBomLine('10.00000000', '10.00');
        $workOrder = $this->makeWorkOrder();

        $workOrderLineRepo = Mockery::mock(WorkOrderLineRepositoryInterface::class);
        $workOrderLineRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['quantity_required'] === '11.00000000';
            })
            ->andReturn((object) []);

        $bomRepo = Mockery::mock(BomRepositoryInterface::class);
        $bomRepo->shouldReceive('findById')->andReturn($bom);

        $bomLineRepo = Mockery::mock(BomLineRepositoryInterface::class);
        $bomLineRepo->shouldReceive('findByBom')->andReturn(new Collection([$bomLine]));

        $workOrderRepo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $workOrderRepo->shouldReceive('create')->andReturn($workOrder);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('withTrashed')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $useCase = new CreateWorkOrderUseCase($bomRepo, $bomLineRepo, $workOrderRepo, $workOrderLineRepo);

        $result = $useCase->execute([
            'bom_id'           => 'bom-uuid-1',
            'quantity_planned' => '1',
            'tenant_id'        => 'tenant-uuid-1',
        ]);

        $this->assertSame('draft', $result->status);
    }

    // -------------------------------------------------------------------------
    // StartWorkOrderUseCase tests
    // -------------------------------------------------------------------------

    public function test_start_throws_when_work_order_not_found(): void
    {
        $repo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new StartWorkOrderUseCase($repo);

        $this->expectException(DomainException::class);

        $useCase->execute('missing-id');
    }

    public function test_start_throws_when_work_order_already_done(): void
    {
        $repo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeWorkOrder('wo-1', 'done'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new StartWorkOrderUseCase($repo);

        $this->expectException(DomainException::class);

        $useCase->execute('wo-1');
    }

    public function test_start_transitions_to_in_progress_and_dispatches_event(): void
    {
        $workOrder = $this->makeWorkOrder('wo-1', 'draft');
        $started   = (object) array_merge((array) $workOrder, ['status' => 'in_progress']);

        $repo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($workOrder);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'in_progress')
            ->once()
            ->andReturn($started);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof WorkOrderStarted);

        $useCase = new StartWorkOrderUseCase($repo);
        $result  = $useCase->execute('wo-1');

        $this->assertSame('in_progress', $result->status);
    }

    // -------------------------------------------------------------------------
    // CompleteWorkOrderUseCase tests
    // -------------------------------------------------------------------------

    public function test_complete_throws_when_not_in_progress(): void
    {
        $repo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeWorkOrder('wo-1', 'draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompleteWorkOrderUseCase($repo);

        $this->expectException(DomainException::class);

        $useCase->execute('wo-1', ['quantity_produced' => '10']);
    }

    public function test_complete_transitions_to_done_and_dispatches_event(): void
    {
        $workOrder = $this->makeWorkOrder('wo-1', 'in_progress');
        $done      = (object) array_merge((array) $workOrder, [
            'status'    => 'done',
            'tenant_id' => 'tenant-uuid-1',
        ]);

        $repo = Mockery::mock(WorkOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($workOrder);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'done')
            ->once()
            ->andReturn($done);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof WorkOrderCompleted);

        $useCase = new CompleteWorkOrderUseCase($repo);
        $result  = $useCase->execute('wo-1', ['quantity_produced' => '10']);

        $this->assertSame('done', $result->status);
    }
}
