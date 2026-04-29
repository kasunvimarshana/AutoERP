<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Application\Contracts\CreateVehicleJobCardServiceInterface;
use Modules\Vehicle\Application\Contracts\CreateVehicleRentalServiceInterface;
use Modules\Vehicle\Application\Contracts\CreateVehicleServiceInterface;
use Tests\TestCase;

class VehicleLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_cannot_be_rented_while_in_service(): void
    {
        $tenantId = 601;
        $this->seedTenant($tenantId);

        /** @var CreateVehicleServiceInterface $createVehicleService */
        $createVehicleService = app(CreateVehicleServiceInterface::class);
        /** @var CreateVehicleRentalServiceInterface $createVehicleRentalService */
        $createVehicleRentalService = app(CreateVehicleRentalServiceInterface::class);

        $vehicle = $createVehicleService->execute([
            'tenant_id' => $tenantId,
            'ownership_type' => 'company_owned',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'vin' => 'VIN-SERVICE-BLOCK-001',
            'registration_number' => 'REG-SERVICE-001',
            'chassis_number' => 'CH-SERVICE-001',
            'service_status' => 'in_maintenance',
            'rental_status' => 'available',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Vehicle cannot be rented while in service workflow.');

        $createVehicleRentalService->execute([
            'tenant_id' => $tenantId,
            'vehicle_id' => (int) $vehicle->getId(),
            'rental_no' => 'RENT-BLOCK-001',
            'pricing_model' => 'daily',
            'base_rate' => '100.000000',
            'units' => '2.000000',
            'tax_rate' => '0.050000',
        ]);
    }

    public function test_vehicle_cannot_be_scheduled_for_service_while_reserved_or_rented(): void
    {
        $tenantId = 602;
        $this->seedTenant($tenantId);

        /** @var CreateVehicleServiceInterface $createVehicleService */
        $createVehicleService = app(CreateVehicleServiceInterface::class);
        /** @var CreateVehicleRentalServiceInterface $createVehicleRentalService */
        $createVehicleRentalService = app(CreateVehicleRentalServiceInterface::class);
        /** @var CreateVehicleJobCardServiceInterface $createVehicleJobCardService */
        $createVehicleJobCardService = app(CreateVehicleJobCardServiceInterface::class);

        $vehicle = $createVehicleService->execute([
            'tenant_id' => $tenantId,
            'ownership_type' => 'company_owned',
            'make' => 'Honda',
            'model' => 'Civic',
            'vin' => 'VIN-RENT-BLOCK-001',
            'registration_number' => 'REG-RENT-001',
            'chassis_number' => 'CH-RENT-001',
            'service_status' => 'none',
            'rental_status' => 'available',
        ]);

        $rental = $createVehicleRentalService->execute([
            'tenant_id' => $tenantId,
            'vehicle_id' => (int) $vehicle->getId(),
            'rental_no' => 'RENT-ACTIVE-001',
            'pricing_model' => 'daily',
            'base_rate' => '100.000000',
            'units' => '1.000000',
            'tax_rate' => '0.000000',
            'rental_status' => 'reserved',
        ]);

        $this->assertNotNull($rental->getId());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Vehicle cannot be scheduled for service while actively reserved/rented.');

        $createVehicleJobCardService->execute([
            'tenant_id' => $tenantId,
            'vehicle_id' => (int) $vehicle->getId(),
            'job_card_no' => 'JC-LOCK-001',
            'service_type' => 'repair',
        ]);
    }

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
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
}
