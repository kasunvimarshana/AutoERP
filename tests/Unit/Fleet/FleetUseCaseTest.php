<?php

namespace Tests\Unit\Fleet;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Fleet\Application\UseCases\LogMaintenanceUseCase;
use Modules\Fleet\Application\UseCases\RegisterVehicleUseCase;
use Modules\Fleet\Application\UseCases\RetireVehicleUseCase;
use Modules\Fleet\Domain\Contracts\MaintenanceRecordRepositoryInterface;
use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Domain\Events\MaintenanceLogged;
use Modules\Fleet\Domain\Events\VehicleRegistered;
use Modules\Fleet\Domain\Events\VehicleRetired;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Fleet Management module use cases.
 *
 * Covers vehicle registration with event dispatch, BCMath cost normalisation,
 * maintenance logging with retired-vehicle guard, and retire lifecycle.
 */
class FleetUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeVehicle(string $status = 'active'): object
    {
        return (object) [
            'id'           => 'vehicle-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'plate_number' => 'ABC-1234',
            'make'         => 'Toyota',
            'model'        => 'Hilux',
            'year'         => 2022,
            'status'       => $status,
        ];
    }

    private function makeMaintenance(): object
    {
        return (object) [
            'id'               => 'maint-uuid-1',
            'tenant_id'        => 'tenant-uuid-1',
            'vehicle_id'       => 'vehicle-uuid-1',
            'maintenance_type' => 'oil_change',
            'cost'             => '50.00000000',
        ];
    }

    // -------------------------------------------------------------------------
    // RegisterVehicleUseCase
    // -------------------------------------------------------------------------

    public function test_register_vehicle_sets_status_active_and_dispatches_event(): void
    {
        $vehicle     = $this->makeVehicle('active');
        $vehicleRepo = Mockery::mock(VehicleRepositoryInterface::class);

        $vehicleRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'active' && $data['plate_number'] === 'ABC-1234')
            ->andReturn($vehicle);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof VehicleRegistered
                && $event->plateNumber === 'ABC-1234');

        $useCase = new RegisterVehicleUseCase($vehicleRepo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'plate_number' => 'ABC-1234',
            'make'         => 'Toyota',
            'model'        => 'Hilux',
            'year'         => 2022,
        ]);

        $this->assertSame('active', $result->status);
    }

    // -------------------------------------------------------------------------
    // LogMaintenanceUseCase
    // -------------------------------------------------------------------------

    public function test_log_maintenance_throws_when_vehicle_not_found(): void
    {
        $vehicleRepo     = Mockery::mock(VehicleRepositoryInterface::class);
        $maintenanceRepo = Mockery::mock(MaintenanceRecordRepositoryInterface::class);

        $vehicleRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new LogMaintenanceUseCase($vehicleRepo, $maintenanceRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'        => 'tenant-uuid-1',
            'vehicle_id'       => 'missing-id',
            'maintenance_type' => 'oil_change',
            'performed_at'     => '2026-01-01',
            'cost'             => '50',
        ]);
    }

    public function test_log_maintenance_throws_when_vehicle_is_retired(): void
    {
        $vehicleRepo     = Mockery::mock(VehicleRepositoryInterface::class);
        $maintenanceRepo = Mockery::mock(MaintenanceRecordRepositoryInterface::class);

        $vehicleRepo->shouldReceive('findById')->andReturn($this->makeVehicle('retired'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new LogMaintenanceUseCase($vehicleRepo, $maintenanceRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/retired/i');

        $useCase->execute([
            'tenant_id'        => 'tenant-uuid-1',
            'vehicle_id'       => 'vehicle-uuid-1',
            'maintenance_type' => 'oil_change',
            'performed_at'     => '2026-01-01',
            'cost'             => '50',
        ]);
    }

    public function test_log_maintenance_normalises_cost_with_bcmath_and_dispatches_event(): void
    {
        $record          = $this->makeMaintenance();
        $vehicleRepo     = Mockery::mock(VehicleRepositoryInterface::class);
        $maintenanceRepo = Mockery::mock(MaintenanceRecordRepositoryInterface::class);

        $vehicleRepo->shouldReceive('findById')->andReturn($this->makeVehicle());
        $maintenanceRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['cost'] === '50.00000000' && $data['maintenance_type'] === 'oil_change')
            ->andReturn($record);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof MaintenanceLogged
                && $event->maintenanceType === 'oil_change');

        $useCase = new LogMaintenanceUseCase($vehicleRepo, $maintenanceRepo);
        $result  = $useCase->execute([
            'tenant_id'        => 'tenant-uuid-1',
            'vehicle_id'       => 'vehicle-uuid-1',
            'maintenance_type' => 'oil_change',
            'performed_at'     => '2026-01-01',
            'cost'             => '50',
        ]);

        $this->assertSame('50.00000000', $result->cost);
    }

    // -------------------------------------------------------------------------
    // RetireVehicleUseCase
    // -------------------------------------------------------------------------

    public function test_retire_throws_when_vehicle_not_found(): void
    {
        $vehicleRepo = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicleRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RetireVehicleUseCase($vehicleRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_retire_throws_when_already_retired(): void
    {
        $vehicleRepo = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicleRepo->shouldReceive('findById')->andReturn($this->makeVehicle('retired'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RetireVehicleUseCase($vehicleRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already retired/i');

        $useCase->execute('vehicle-uuid-1');
    }

    public function test_retire_transitions_to_retired_and_dispatches_event(): void
    {
        $vehicle = $this->makeVehicle('active');
        $retired = (object) array_merge((array) $vehicle, ['status' => 'retired']);

        $vehicleRepo = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicleRepo->shouldReceive('findById')->andReturn($vehicle);
        $vehicleRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'retired')
            ->once()
            ->andReturn($retired);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof VehicleRetired
                && $event->vehicleId === 'vehicle-uuid-1');

        $useCase = new RetireVehicleUseCase($vehicleRepo);
        $result  = $useCase->execute('vehicle-uuid-1');

        $this->assertSame('retired', $result->status);
    }
}
