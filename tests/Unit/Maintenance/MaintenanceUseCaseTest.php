<?php

namespace Tests\Unit\Maintenance;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Maintenance\Application\UseCases\CompleteMaintenanceOrderUseCase;
use Modules\Maintenance\Application\UseCases\CreateMaintenanceOrderUseCase;
use Modules\Maintenance\Application\UseCases\CreateMaintenanceRequestUseCase;
use Modules\Maintenance\Application\UseCases\DecommissionEquipmentUseCase;
use Modules\Maintenance\Application\UseCases\RegisterEquipmentUseCase;
use Modules\Maintenance\Application\UseCases\StartMaintenanceOrderUseCase;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceRequestRepositoryInterface;
use Modules\Maintenance\Domain\Events\EquipmentDecommissioned;
use Modules\Maintenance\Domain\Events\EquipmentRegistered;
use Modules\Maintenance\Domain\Events\MaintenanceOrderCompleted;
use Modules\Maintenance\Domain\Events\MaintenanceOrderStarted;
use Modules\Maintenance\Domain\Events\MaintenanceRequestCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Maintenance module use cases.
 *
 * Covers equipment registration, decommission lifecycle, maintenance request creation,
 * maintenance order (draft→in_progress→done) with BCMath cost tracking, and guard rules.
 */
class MaintenanceUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEquipment(string $status = 'active'): object
    {
        return (object) [
            'id'            => 'equip-uuid-1',
            'tenant_id'     => 'tenant-uuid-1',
            'name'          => 'CNC Machine #1',
            'serial_number' => 'SN-001',
            'status'        => $status,
        ];
    }

    private function makeRequest(): object
    {
        return (object) [
            'id'           => 'req-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'equip-uuid-1',
            'requested_by' => 'operator@example.com',
            'description'  => 'Machine vibrating excessively.',
            'priority'     => 'high',
            'status'       => 'new',
        ];
    }

    private function makeOrder(string $status = 'draft'): object
    {
        return (object) [
            'id'           => 'order-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'equip-uuid-1',
            'reference'    => 'MO-2026-000001',
            'order_type'   => 'corrective',
            'labor_cost'   => '100.00000000',
            'parts_cost'   => '50.00000000',
            'status'       => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // RegisterEquipmentUseCase
    // -------------------------------------------------------------------------

    public function test_register_equipment_sets_active_status_and_dispatches_event(): void
    {
        $equipment     = $this->makeEquipment('active');
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);

        $equipmentRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'active' && $data['serial_number'] === 'SN-001')
            ->andReturn($equipment);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof EquipmentRegistered
                && $event->serialNumber === 'SN-001'
                && $event->name === 'CNC Machine #1');

        $useCase = new RegisterEquipmentUseCase($equipmentRepo);
        $result  = $useCase->execute([
            'tenant_id'     => 'tenant-uuid-1',
            'name'          => 'CNC Machine #1',
            'serial_number' => 'SN-001',
        ]);

        $this->assertSame('active', $result->status);
    }

    // -------------------------------------------------------------------------
    // DecommissionEquipmentUseCase
    // -------------------------------------------------------------------------

    public function test_decommission_throws_when_equipment_not_found(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $equipmentRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DecommissionEquipmentUseCase($equipmentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_decommission_throws_when_already_decommissioned(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $equipmentRepo->shouldReceive('findById')->andReturn($this->makeEquipment('decommissioned'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DecommissionEquipmentUseCase($equipmentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already decommissioned/i');

        $useCase->execute('equip-uuid-1');
    }

    public function test_decommission_transitions_to_decommissioned_and_dispatches_event(): void
    {
        $equipment   = $this->makeEquipment('active');
        $decommissioned = (object) array_merge((array) $equipment, ['status' => 'decommissioned']);

        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $equipmentRepo->shouldReceive('findById')->andReturn($equipment);
        $equipmentRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'decommissioned')
            ->andReturn($decommissioned);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof EquipmentDecommissioned
                && $event->equipmentId === 'equip-uuid-1');

        $useCase = new DecommissionEquipmentUseCase($equipmentRepo);
        $result  = $useCase->execute('equip-uuid-1');

        $this->assertSame('decommissioned', $result->status);
    }

    // -------------------------------------------------------------------------
    // CreateMaintenanceRequestUseCase
    // -------------------------------------------------------------------------

    public function test_create_request_throws_when_equipment_not_found(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $requestRepo   = Mockery::mock(MaintenanceRequestRepositoryInterface::class);

        $equipmentRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateMaintenanceRequestUseCase($equipmentRepo, $requestRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'missing-id',
            'requested_by' => 'op@example.com',
            'description'  => 'Broken.',
        ]);
    }

    public function test_create_request_throws_for_decommissioned_equipment(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $requestRepo   = Mockery::mock(MaintenanceRequestRepositoryInterface::class);

        $equipmentRepo->shouldReceive('findById')->andReturn($this->makeEquipment('decommissioned'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateMaintenanceRequestUseCase($equipmentRepo, $requestRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/decommissioned/i');

        $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'equip-uuid-1',
            'requested_by' => 'op@example.com',
            'description'  => 'Broken.',
        ]);
    }

    public function test_create_request_creates_with_new_status_and_dispatches_event(): void
    {
        $request       = $this->makeRequest();
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $requestRepo   = Mockery::mock(MaintenanceRequestRepositoryInterface::class);

        $equipmentRepo->shouldReceive('findById')->andReturn($this->makeEquipment());
        $requestRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'new' && $data['priority'] === 'high')
            ->andReturn($request);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof MaintenanceRequestCreated
                && $event->requestId === 'req-uuid-1'
                && $event->requestedBy === 'operator@example.com');

        $useCase = new CreateMaintenanceRequestUseCase($equipmentRepo, $requestRepo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'equip-uuid-1',
            'requested_by' => 'operator@example.com',
            'description'  => 'Machine vibrating excessively.',
            'priority'     => 'high',
        ]);

        $this->assertSame('new', $result->status);
    }

    // -------------------------------------------------------------------------
    // CreateMaintenanceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_create_order_throws_when_equipment_not_found(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $equipmentRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateMaintenanceOrderUseCase($equipmentRepo, $orderRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'missing-id',
            'order_type'   => 'preventive',
        ]);
    }

    public function test_create_order_normalises_costs_with_bcmath(): void
    {
        $order         = $this->makeOrder('draft');
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $equipmentRepo->shouldReceive('findById')->andReturn($this->makeEquipment());
        $orderRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) =>
                $data['labor_cost'] === '100.00000000'
                && $data['parts_cost'] === '50.00000000'
                && $data['status'] === 'draft')
            ->andReturn($order);

        // Mock DB::transaction and DB::table (used for sequence-based reference generation)
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')
            ->with('maintenance_orders')
            ->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $useCase = new CreateMaintenanceOrderUseCase($equipmentRepo, $orderRepo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'equipment_id' => 'equip-uuid-1',
            'order_type'   => 'corrective',
            'labor_cost'   => '100',
            'parts_cost'   => '50',
        ]);

        $this->assertSame('draft', $result->status);
        $this->assertSame('100.00000000', $result->labor_cost);
        $this->assertSame('50.00000000', $result->parts_cost);
    }

    // -------------------------------------------------------------------------
    // StartMaintenanceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_start_order_throws_when_order_not_found(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new StartMaintenanceOrderUseCase($equipmentRepo, $orderRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_start_order_throws_when_already_done(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($this->makeOrder('done'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new StartMaintenanceOrderUseCase($equipmentRepo, $orderRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/done or cancelled/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_start_order_throws_when_already_in_progress(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($this->makeOrder('in_progress'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new StartMaintenanceOrderUseCase($equipmentRepo, $orderRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already in progress/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_start_order_transitions_to_in_progress_and_dispatches_event(): void
    {
        $order      = $this->makeOrder('draft');
        $inProgress = (object) array_merge((array) $order, ['status' => 'in_progress']);

        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($order);
        $orderRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'in_progress')
            ->andReturn($inProgress);
        $equipmentRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'under_maintenance')
            ->andReturn($this->makeEquipment('under_maintenance'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof MaintenanceOrderStarted
                && $event->orderId === 'order-uuid-1'
                && $event->orderType === 'corrective');

        $useCase = new StartMaintenanceOrderUseCase($equipmentRepo, $orderRepo);
        $result  = $useCase->execute('order-uuid-1');

        $this->assertSame('in_progress', $result->status);
    }

    // -------------------------------------------------------------------------
    // CompleteMaintenanceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_complete_order_throws_when_order_not_found(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CompleteMaintenanceOrderUseCase($equipmentRepo, $orderRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_complete_order_throws_when_not_in_progress(): void
    {
        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($this->makeOrder('draft'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CompleteMaintenanceOrderUseCase($equipmentRepo, $orderRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/in-progress/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_complete_order_transitions_to_done_with_bcmath_costs_and_dispatches_event(): void
    {
        $order = $this->makeOrder('in_progress');
        $done  = (object) array_merge((array) $order, [
            'status'     => 'done',
            'labor_cost' => '200.00000000',
            'parts_cost' => '75.00000000',
        ]);

        $equipmentRepo = Mockery::mock(EquipmentRepositoryInterface::class);
        $orderRepo     = Mockery::mock(MaintenanceOrderRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($order);
        $orderRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) =>
                $data['status'] === 'done'
                && $data['labor_cost'] === '200.00000000'
                && $data['parts_cost'] === '75.00000000')
            ->andReturn($done);
        $equipmentRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'active')
            ->andReturn($this->makeEquipment('active'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof MaintenanceOrderCompleted
                && $event->orderId === 'order-uuid-1'
                && $event->laborCost === '200.00000000'
                && $event->partsCost === '75.00000000');

        $useCase = new CompleteMaintenanceOrderUseCase($equipmentRepo, $orderRepo);
        $result  = $useCase->execute('order-uuid-1', [
            'labor_cost' => '200',
            'parts_cost' => '75',
        ]);

        $this->assertSame('done', $result->status);
        $this->assertSame('200.00000000', $result->labor_cost);
        $this->assertSame('75.00000000', $result->parts_cost);
    }
}
