<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\ServiceCenter\Domain\Entities\ServiceJob;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceJobRepositoryInterface;
use Modules\ServiceCenter\Domain\ValueObjects\JobType;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;
use Tests\TestCase;

class ServiceJobRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_job_repository_saves_updates_and_filters_by_tenant(): void
    {
        $this->seedTenant(201);
        $this->seedTenant(202);

        [$vehicleId201, $driverId201] = $this->seedVehicleAndDriver(201, '201');
        [$vehicleId202] = $this->seedVehicleAndDriver(202, '202');

        /** @var ServiceJobRepositoryInterface $repo */
        $repo = app(ServiceJobRepositoryInterface::class);

        $job1 = $repo->save($this->makeServiceJob(
            tenantId: 201,
            vehicleId: $vehicleId201,
            driverId: $driverId201,
            jobNumber: 'SJ-201-001',
        ));

        $this->assertNotNull($job1->id);
        $this->assertSame(201, $job1->tenantId);
        $this->assertSame('SJ-201-001', $job1->jobNumber);
        $this->assertSame(JobType::Maintenance, $job1->jobType);
        $this->assertSame(ServiceJobStatus::Pending, $job1->status);
        $this->assertSame(1, $job1->rowVersion);
        $this->assertSame('17500.000000', $job1->totalCost);

        $repo->save($this->makeServiceJob(
            tenantId: 202,
            vehicleId: $vehicleId202,
            driverId: null,
            jobNumber: 'SJ-202-001',
        ));

        $tenant201 = $repo->findByTenant(201);
        $tenant202 = $repo->findByTenant(202);
        $this->assertCount(1, $tenant201);
        $this->assertSame('SJ-201-001', $tenant201[0]->jobNumber);
        $this->assertCount(1, $tenant202);
        $this->assertSame('SJ-202-001', $tenant202[0]->jobNumber);

        $found = $repo->findById((int) $job1->id, 201);
        $this->assertInstanceOf(ServiceJob::class, $found);
        $this->assertSame((int) $job1->id, (int) $found->id);

        $this->assertNull($repo->findById((int) $job1->id, 202));

        $byVehicle = $repo->findByVehicle($vehicleId201, 201);
        $this->assertCount(1, $byVehicle);
        $this->assertSame('SJ-201-001', $byVehicle[0]->jobNumber);

        $repo->updateStatus((int) $job1->id, 201, ServiceJobStatus::InProgress);
        $refreshed = $repo->findById((int) $job1->id, 201);
        $this->assertSame(ServiceJobStatus::InProgress, $refreshed->status);

        $repo->save($refreshed);
        $rowVersion = DB::table('fleet_service_jobs')->where('id', $job1->id)->value('row_version');
        $this->assertGreaterThan(1, (int) $rowVersion);

        $repo->delete((int) $job1->id, 201);
        $this->assertNull($repo->findById((int) $job1->id, 201));
    }

    private function makeServiceJob(
        int $tenantId,
        int $vehicleId,
        ?int $driverId,
        string $jobNumber,
    ): ServiceJob {
        return new ServiceJob(
            id: null,
            tenantId: $tenantId,
            orgUnitId: null,
            vehicleId: $vehicleId,
            driverId: $driverId,
            jobNumber: $jobNumber,
            jobType: JobType::Maintenance,
            status: ServiceJobStatus::Pending,
            scheduledAt: new DateTimeImmutable('2025-08-01 09:00:00'),
            startedAt: null,
            completedAt: null,
            odometerIn: '45600.50',
            odometerOut: null,
            description: 'Quarterly preventive service',
            partsCost: '9000.000000',
            labourCost: '8500.000000',
            totalCost: '17500.000000',
            technicianNotes: null,
            customerApproval: false,
            metadata: null,
            isActive: true,
            rowVersion: 1,
        );
    }

    private function seedTenant(int $tenantId): void
    {
        if (DB::table('tenants')->where('id', $tenantId)->exists()) {
            return;
        }

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant ' . $tenantId,
            'slug' => 'tenant-' . $tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedVehicleAndDriver(int $tenantId, string $suffix): array
    {
        $vehicleTypeId = DB::table('fleet_vehicle_types')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'SUV ' . $suffix,
            'description' => null,
            'base_daily_rate' => '12000.000000',
            'base_hourly_rate' => '1000.000000',
            'seating_capacity' => 7,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $vehicleId = DB::table('fleet_vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'vehicle_type_id' => $vehicleTypeId,
            'registration_number' => 'SV-' . $suffix,
            'make' => 'Nissan',
            'model' => 'X-Trail',
            'year' => 2022,
            'color' => 'Black',
            'vin_number' => null,
            'engine_number' => null,
            'ownership_type' => 'owned',
            'owner_supplier_id' => null,
            'owner_commission_pct' => '0.00',
            'is_rentable' => true,
            'is_serviceable' => true,
            'current_state' => 'available',
            'current_odometer' => '45500.00',
            'fuel_type' => 'diesel',
            'fuel_capacity' => null,
            'seating_capacity' => 7,
            'transmission' => 'automatic',
            'asset_account_id' => null,
            'accum_depreciation_account_id' => null,
            'depreciation_expense_account_id' => null,
            'rental_revenue_account_id' => null,
            'service_revenue_account_id' => null,
            'acquisition_cost' => null,
            'acquired_at' => null,
            'disposed_at' => null,
            'metadata' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $driverId = DB::table('fleet_drivers')->insertGetId([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'employee_id' => null,
            'driver_code' => 'DRV-' . $suffix,
            'full_name' => 'Driver ' . $suffix,
            'phone' => null,
            'email' => null,
            'address' => null,
            'compensation_type' => 'salary',
            'per_trip_rate' => '0.000000',
            'commission_pct' => '0.00',
            'status' => 'available',
            'metadata' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        return [$vehicleId, $driverId];
    }
}
