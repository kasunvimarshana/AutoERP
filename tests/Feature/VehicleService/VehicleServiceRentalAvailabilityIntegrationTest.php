<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Services\RentalAvailabilityService;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use Tests\TestCase;

final class VehicleServiceRentalAvailabilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_progress_service_jobs_project_vehicle_unavailability_until_the_last_job_closes(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $vehicleId): void {
            $firstJob = $this->createServiceJob($tenantId, $customerId, $vehicleId, 'VSJ-AVAILABILITY-001');
            $secondJob = $this->createServiceJob($tenantId, $customerId, $vehicleId, 'VSJ-AVAILABILITY-002');
            $statusService = app(VehicleServiceStatusService::class);

            $statusService->change($firstJob, VehicleServiceJobStatus::InProgress, reason: 'Workshop work started.');

            self::assertSame(
                VehicleStatus::UnderService,
                Vehicle::query()->findOrFail($vehicleId)->status,
            );
            self::assertSame(0, app(RentalAvailabilityService::class)->queryAvailable(
                $tenantId,
                null,
                '2026-07-20 08:00:00',
                '2026-07-21 08:00:00',
            )->count());

            try {
                app(RentalAvailabilityService::class)->assertVehicle(
                    $tenantId,
                    null,
                    $vehicleId,
                    '2026-07-20 08:00:00',
                    '2026-07-21 08:00:00',
                );
                self::fail('An under-service vehicle should not be available for rental.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame(
                    'Vehicle is not available for rental in its current status.',
                    $exception->getMessage(),
                );
            }

            $statusService->change($secondJob, VehicleServiceJobStatus::InProgress, reason: 'Additional workshop work started.');
            $statusService->change(
                VehicleServiceJob::query()->findOrFail($firstJob->getKey()),
                VehicleServiceJobStatus::Completed,
                reason: 'First workshop job completed.',
            );

            self::assertSame(
                VehicleStatus::UnderService,
                Vehicle::query()->findOrFail($vehicleId)->status,
                'Another in-progress service job still owns the vehicle unavailability.',
            );

            $statusService->change(
                VehicleServiceJob::query()->findOrFail($secondJob->getKey()),
                VehicleServiceJobStatus::Cancelled,
                reason: 'Remaining workshop job cancelled.',
            );

            self::assertSame(
                VehicleStatus::Active,
                Vehicle::query()->findOrFail($vehicleId)->status,
            );
            self::assertSame(1, app(RentalAvailabilityService::class)->queryAvailable(
                $tenantId,
                null,
                '2026-07-20 08:00:00',
                '2026-07-21 08:00:00',
            )->count());
        });

        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehicleId,
            'old_status' => VehicleStatus::Active->value,
            'new_status' => VehicleStatus::UnderService->value,
        ]);
        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehicleId,
            'old_status' => VehicleStatus::UnderService->value,
            'new_status' => VehicleStatus::Active->value,
        ]);
    }

    public function test_rented_vehicle_cannot_enter_an_in_progress_service_job(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId, VehicleStatus::Rented);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only an active vehicle can enter an in-progress service job.');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $customerId, $vehicleId): void {
            $job = $this->createServiceJob($tenantId, $customerId, $vehicleId, 'VSJ-AVAILABILITY-003');

            app(VehicleServiceStatusService::class)->change(
                $job,
                VehicleServiceJobStatus::InProgress,
                reason: 'Invalid overlapping workshop start.',
            );
        });
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'VSA',
            'name' => 'Vehicle Service Availability Currency',
            'symbol' => 'VSA',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(int $currencyId): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'VS-AVAILABILITY',
            'name' => 'Vehicle Service Availability Tenant',
            'slug' => 'vehicle-service-availability-tenant',
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, int $currencyId): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-VS-AVAILABILITY',
            'code' => 'CUS-VS-AVAILABILITY',
            'name' => 'Vehicle Service Availability Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId, VehicleStatus $status = VehicleStatus::Active): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => 'VEH-VS-AVAILABILITY-'.$status->value,
            'code' => 'VEH-VS-'.$status->value,
            'registration_number' => 'VS-'.strtoupper(substr(hash('sha256', $status->value), 0, 6)),
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createServiceJob(
        int $tenantId,
        int $customerId,
        int $vehicleId,
        string $jobNumber,
    ): VehicleServiceJob {
        return VehicleServiceJob::query()->forceCreate([
            'tenant_id' => $tenantId,
            'job_number' => $jobNumber,
            'job_date' => '2026-07-17',
            'customer_id' => $customerId,
            'bill_to_customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'supervisor_commission_type' => 'none',
            'supervisor_commission_value' => '0.000000',
            'supervisor_commission_amount' => '0.000000',
            'status' => VehicleServiceJobStatus::Draft->value,
            'subtotal' => '0.000000',
            'discount_total' => '0.000000',
            'tax_total' => '0.000000',
            'charge_total' => '0.000000',
            'grand_total' => '0.000000',
        ]);
    }
}
