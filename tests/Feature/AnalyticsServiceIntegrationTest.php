<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\Contracts\AnalyticsServiceInterface;
use Modules\Analytics\Application\DTOs\CreateAnalyticsSnapshotDTO;
use Tests\TestCase;

class AnalyticsServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_service_computes_summary_and_persists_snapshot(): void
    {
        $this->seedTenant(301);
        $this->seedTenant(302);

        [$vehicle301, $customer301] = $this->seedVehicleAndCustomer(301, '301');
        [$vehicle302, $customer302] = $this->seedVehicleAndCustomer(302, '302');

        $this->seedRental(301, $customer301, $vehicle301, 'AR-301-1', 'completed', '12000.000000');
        $this->seedRental(301, $customer301, $vehicle301, 'AR-301-2', 'active', '8000.000000');
        $this->seedServiceJob(301, $vehicle301, 'AS-301-1', 'completed', '3000.000000');

        $this->seedRental(302, $customer302, $vehicle302, 'AR-302-1', 'completed', '9900.000000');
        $this->seedServiceJob(302, $vehicle302, 'AS-302-1', 'completed', '1000.000000');

        /** @var AnalyticsServiceInterface $service */
        $service = app(AnalyticsServiceInterface::class);

        $summary = $service->getSummary(301);
        $this->assertSame(2, $summary['total_rentals']);
        $this->assertSame(1, $summary['completed_rentals']);
        $this->assertSame(1, $summary['active_rentals']);
        $this->assertSame('12000.000000', $summary['total_revenue']);
        $this->assertSame('3000.000000', $summary['total_service_cost']);
        $this->assertSame('9000.000000', $summary['net_revenue']);

        $snapshot = $service->createSnapshot(new CreateAnalyticsSnapshotDTO(
            tenantId: 301,
            orgUnitId: null,
            summaryDate: '2026-05-02',
            metadata: ['source' => 'integration-test'],
        ));

        $this->assertNotNull($snapshot->id);
        $this->assertSame(301, $snapshot->tenantId);
        $this->assertSame('2026-05-02', $snapshot->summaryDate);
        $this->assertSame('9000.000000', $snapshot->netRevenue);

        $list = $service->listSnapshots(301);
        $this->assertCount(1, $list);

        $service->deleteSnapshot((int) $snapshot->id, 301);
        $this->assertCount(0, $service->listSnapshots(301));
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

    private function seedVehicleAndCustomer(int $tenantId, string $suffix): array
    {
        $customerId = DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'customer_code' => 'AN-CUST-' . $suffix,
            'name' => 'Analytics Customer ' . $suffix,
            'type' => 'individual',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'credit_limit' => '0.000000',
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $typeId = DB::table('fleet_vehicle_types')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Analytics Type ' . $suffix,
            'description' => null,
            'base_daily_rate' => '10000.000000',
            'base_hourly_rate' => '800.000000',
            'seating_capacity' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $vehicleId = DB::table('fleet_vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'vehicle_type_id' => $typeId,
            'registration_number' => 'AN-VEH-' . $suffix,
            'make' => 'Toyota',
            'model' => 'Prius',
            'year' => 2022,
            'color' => 'Silver',
            'vin_number' => null,
            'engine_number' => null,
            'ownership_type' => 'owned',
            'owner_supplier_id' => null,
            'owner_commission_pct' => '0.00',
            'is_rentable' => true,
            'is_serviceable' => true,
            'current_state' => 'available',
            'current_odometer' => '21000.00',
            'fuel_type' => 'hybrid',
            'fuel_capacity' => null,
            'seating_capacity' => 5,
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

        return [$vehicleId, $customerId];
    }

    private function seedRental(int $tenantId, int $customerId, int $vehicleId, string $number, string $status, string $total): void
    {
        DB::table('fleet_rentals')->insert([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'driver_id' => null,
            'rental_number' => $number,
            'rental_type' => 'self_drive',
            'status' => $status,
            'pickup_location' => 'Main',
            'return_location' => 'Main',
            'scheduled_start_at' => '2026-05-01 08:00:00',
            'scheduled_end_at' => '2026-05-02 08:00:00',
            'actual_start_at' => null,
            'actual_end_at' => null,
            'start_odometer' => null,
            'end_odometer' => null,
            'rate_per_day' => '8000.000000',
            'estimated_days' => '1.0000',
            'actual_days' => null,
            'subtotal' => $total,
            'discount_amount' => '0.000000',
            'tax_amount' => '0.000000',
            'total_amount' => $total,
            'deposit_amount' => '0.000000',
            'notes' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'metadata' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedServiceJob(int $tenantId, int $vehicleId, string $jobNumber, string $status, string $totalCost): void
    {
        DB::table('fleet_service_jobs')->insert([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'vehicle_id' => $vehicleId,
            'driver_id' => null,
            'job_number' => $jobNumber,
            'job_type' => 'maintenance',
            'status' => $status,
            'scheduled_at' => '2026-05-01 09:00:00',
            'started_at' => null,
            'completed_at' => null,
            'odometer_in' => null,
            'odometer_out' => null,
            'description' => null,
            'parts_cost' => $totalCost,
            'labour_cost' => '0.000000',
            'total_cost' => $totalCost,
            'technician_notes' => null,
            'customer_approval' => false,
            'metadata' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
