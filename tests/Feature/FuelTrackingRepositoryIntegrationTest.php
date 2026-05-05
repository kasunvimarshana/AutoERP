<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\FuelTracking\Domain\Entities\FuelLog;
use Modules\FuelTracking\Domain\RepositoryInterfaces\FuelLogRepositoryInterface;
use Modules\FuelTracking\Domain\ValueObjects\FuelType;
use Modules\FuelTracking\Infrastructure\Persistence\Eloquent\Repositories\EloquentFuelLogRepository;
use Tests\TestCase;

class FuelTrackingRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private FuelLogRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentFuelLogRepository();
        $this->seedTenant(951);
        $this->seedTenant(952);
    }

    public function test_fuel_log_crud_tenant_isolation_and_soft_delete(): void
    {
        $now      = new DateTimeImmutable('2026-05-02 10:00:00');
        $vehicleA = 'aaaaaaaa-0000-0000-0000-000000000001';
        $vehicleB = 'bbbbbbbb-0000-0000-0000-000000000001';
        $driverA  = 'cccccccc-0000-0000-0000-000000000001';

        $logA = $this->makeLog(
            id: 'aabbccdd-0000-0000-0000-000000000001',
            tenantId: '951',
            orgUnitId: '951',
            logNumber: 'FL-951-001',
            vehicleId: $vehicleA,
            driverId: $driverA,
            litres: '55.000000',
            costPerLitre: '1.850000',
            totalCost: '101.750000',
            now: $now,
        );
        $savedA = $this->repository->save($logA);

        $this->assertSame('FL-951-001', $savedA->logNumber);
        $this->assertSame('55.000000', $savedA->litres);
        $this->assertSame('1.850000', $savedA->costPerLitre);
        $this->assertSame('101.750000', $savedA->totalCost);
        $this->assertSame(FuelType::Diesel, $savedA->fuelType);

        // Tenant B log
        $logB = $this->makeLog(
            id: 'aabbccdd-0000-0000-0000-000000000002',
            tenantId: '952',
            orgUnitId: '952',
            logNumber: 'FL-952-001',
            vehicleId: $vehicleB,
            driverId: null,
            litres: '40.000000',
            costPerLitre: '1.900000',
            totalCost: '76.000000',
            now: $now,
        );
        $this->repository->save($logB);

        // findById
        $found = $this->repository->findById($savedA->id);
        $this->assertNotNull($found);
        $this->assertSame($savedA->id, $found->id);

        // Tenant isolation via findByTenant
        $byTenant = $this->repository->findByTenant('951', '951');
        $this->assertCount(1, $byTenant);
        $this->assertSame('FL-951-001', $byTenant[0]->logNumber);

        // findByVehicle
        $byVehicle = $this->repository->findByVehicle('951', $vehicleA);
        $this->assertCount(1, $byVehicle);
        $this->assertSame('FL-951-001', $byVehicle[0]->logNumber);

        // findByDriver
        $byDriver = $this->repository->findByDriver('951', $driverA);
        $this->assertCount(1, $byDriver);

        // row_version increments on second save
        $updated = $this->repository->save(new FuelLog(
            id: $savedA->id,
            tenantId: $savedA->tenantId,
            orgUnitId: $savedA->orgUnitId,
            rowVersion: $savedA->rowVersion,
            logNumber: $savedA->logNumber,
            vehicleId: $savedA->vehicleId,
            driverId: $savedA->driverId,
            fuelType: $savedA->fuelType,
            odoReading: $savedA->odoReading,
            litres: '60.000000',
            costPerLitre: $savedA->costPerLitre,
            totalCost: '111.000000',
            stationName: 'Updated Station',
            filledAt: $savedA->filledAt,
            notes: $savedA->notes,
            metadata: $savedA->metadata,
            isActive: $savedA->isActive,
            createdAt: null,
            updatedAt: null,
        ));
        $this->assertGreaterThan($savedA->rowVersion, $updated->rowVersion);
        $this->assertSame('60.000000', $updated->litres);

        // soft delete
        $this->repository->delete($savedA->id);
        $this->assertNull($this->repository->findById($savedA->id));
    }

    private function makeLog(
        string $id,
        string $tenantId,
        string $orgUnitId,
        string $logNumber,
        string $vehicleId,
        ?string $driverId,
        string $litres,
        string $costPerLitre,
        string $totalCost,
        DateTimeImmutable $now,
    ): FuelLog {
        return new FuelLog(
            id: $id,
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            rowVersion: 1,
            logNumber: $logNumber,
            vehicleId: $vehicleId,
            driverId: $driverId,
            fuelType: FuelType::Diesel,
            odoReading: '45000.00',
            litres: $litres,
            costPerLitre: $costPerLitre,
            totalCost: $totalCost,
            stationName: 'Test Station',
            filledAt: $now,
            notes: 'Test note',
            metadata: ['ref' => 'test'],
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private function seedTenant(int $tenantId): void
    {
        if (DB::table('tenants')->where('id', $tenantId)->exists()) {
            return;
        }

        DB::table('tenants')->insert([
            'id'                   => $tenantId,
            'name'                 => 'Tenant ' . $tenantId,
            'slug'                 => 'tenant-' . $tenantId,
            'domain'               => null,
            'logo_path'            => null,
            'database_config'      => null,
            'mail_config'          => null,
            'cache_config'         => null,
            'queue_config'         => null,
            'feature_flags'        => null,
            'api_keys'             => null,
            'settings'             => null,
            'plan'                 => 'free',
            'tenant_plan_id'       => null,
            'status'               => 'active',
            'active'               => true,
            'trial_ends_at'        => null,
            'subscription_ends_at' => null,
            'created_at'           => now(),
            'updated_at'           => now(),
            'deleted_at'           => null,
        ]);
    }
}
