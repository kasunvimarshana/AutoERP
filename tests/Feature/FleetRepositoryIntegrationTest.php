<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Fleet\Domain\Entities\Vehicle;
use Modules\Fleet\Domain\Entities\VehicleDocument;
use Modules\Fleet\Domain\Entities\VehicleType;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleTypeRepositoryInterface;
use Tests\TestCase;

class FleetRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_type_repository_saves_updates_and_lists_by_tenant(): void
    {
        $this->seedTenant(11);
        $this->seedTenant(12);

        /** @var VehicleTypeRepositoryInterface $repository */
        $repository = app(VehicleTypeRepositoryInterface::class);

        $created = $repository->save(new VehicleType(
            tenantId: 11,
            name: 'SUV',
            description: 'Sport utility vehicle',
            baseDailyRate: '25000.000000',
            baseHourlyRate: '1800.000000',
            seatingCapacity: 7,
            isActive: true,
        ));

        $this->assertNotNull($created->id);
        $this->assertSame('SUV', $created->name);

        $updated = $repository->save(new VehicleType(
            tenantId: 11,
            name: 'Premium SUV',
            description: 'Upgraded sport utility vehicle',
            baseDailyRate: '28000.000000',
            baseHourlyRate: '2000.000000',
            seatingCapacity: 7,
            isActive: true,
            id: $created->id,
        ));

        $this->assertSame($created->id, $updated->id);
        $this->assertSame('Premium SUV', $updated->name);

        $rowVersion = DB::table('fleet_vehicle_types')->where('id', $created->id)->value('row_version');
        $this->assertSame(2, (int) $rowVersion);

        $tenant11Types = $repository->listByTenant(11);
        $tenant12Types = $repository->listByTenant(12);

        $this->assertCount(1, $tenant11Types);
        $this->assertSame('Premium SUV', $tenant11Types[0]->name);
        $this->assertCount(0, $tenant12Types);
    }

    public function test_vehicle_repository_filters_and_updates_state_and_odometer(): void
    {
        $this->seedTenant(21);
        $this->seedTenant(22);

        /** @var VehicleTypeRepositoryInterface $vehicleTypeRepository */
        $vehicleTypeRepository = app(VehicleTypeRepositoryInterface::class);
        /** @var VehicleRepositoryInterface $vehicleRepository */
        $vehicleRepository = app(VehicleRepositoryInterface::class);

        $type21 = $vehicleTypeRepository->save(new VehicleType(
            tenantId: 21,
            name: 'Sedan',
            description: null,
            baseDailyRate: '15000.000000',
            baseHourlyRate: '1200.000000',
            seatingCapacity: 5,
            isActive: true,
        ));

        $type22 = $vehicleTypeRepository->save(new VehicleType(
            tenantId: 22,
            name: 'Van',
            description: null,
            baseDailyRate: '18000.000000',
            baseHourlyRate: '1400.000000',
            seatingCapacity: 8,
            isActive: true,
        ));

        $available = $vehicleRepository->save($this->makeVehicle(21, (int) $type21->id, 'CAR-2101', 'available', true, true));
        $vehicleRepository->save($this->makeVehicle(21, (int) $type21->id, 'CAR-2102', 'rented', true, true));
        $vehicleRepository->save($this->makeVehicle(22, (int) $type22->id, 'CAR-2201', 'available', true, true));

        $forRental = $vehicleRepository->listAvailableForRental(21);
        $forService = $vehicleRepository->listAvailableForService(21);

        $this->assertCount(1, $forRental);
        $this->assertSame('CAR-2101', $forRental[0]->registrationNumber);
        $this->assertCount(1, $forService);
        $this->assertSame('CAR-2101', $forService[0]->registrationNumber);

        $vehicleRepository->updateState((int) $available->id, 'maintenance', now()->toDateTimeString());
        $vehicleRepository->updateOdometer((int) $available->id, '12500.50');

        $updated = $vehicleRepository->findByRegistration(21, 'CAR-2101');

        $this->assertInstanceOf(Vehicle::class, $updated);
        $this->assertSame('maintenance', $updated->currentState);
        $this->assertSame(0, bccomp($updated->currentOdometer, '12500.50', 2));
    }

    public function test_vehicle_document_repository_lists_expiring_documents_by_tenant_window(): void
    {
        $this->seedTenant(31);
        $this->seedTenant(32);

        /** @var VehicleTypeRepositoryInterface $vehicleTypeRepository */
        $vehicleTypeRepository = app(VehicleTypeRepositoryInterface::class);
        /** @var VehicleRepositoryInterface $vehicleRepository */
        $vehicleRepository = app(VehicleRepositoryInterface::class);
        /** @var VehicleDocumentRepositoryInterface $documentRepository */
        $documentRepository = app(VehicleDocumentRepositoryInterface::class);

        $type31 = $vehicleTypeRepository->save(new VehicleType(
            tenantId: 31,
            name: 'Hatchback',
            description: null,
            baseDailyRate: '9000.000000',
            baseHourlyRate: '700.000000',
            seatingCapacity: 5,
            isActive: true,
        ));

        $type32 = $vehicleTypeRepository->save(new VehicleType(
            tenantId: 32,
            name: 'Mini Van',
            description: null,
            baseDailyRate: '12000.000000',
            baseHourlyRate: '950.000000',
            seatingCapacity: 7,
            isActive: true,
        ));

        $vehicle31 = $vehicleRepository->save($this->makeVehicle(31, (int) $type31->id, 'CAR-3101', 'available', true, true));
        $vehicle32 = $vehicleRepository->save($this->makeVehicle(32, (int) $type32->id, 'CAR-3201', 'available', true, true));

        $expiringSoon = $documentRepository->save(new VehicleDocument(
            tenantId: 31,
            vehicleId: (int) $vehicle31->id,
            documentType: 'insurance',
            documentNumber: 'INS-31-001',
            issuingAuthority: 'Local Insurer',
            issueDate: now()->subMonths(11)->toDateString(),
            expiryDate: now()->addDays(10)->toDateString(),
            filePath: null,
            notes: null,
            isActive: true,
        ));

        $documentRepository->save(new VehicleDocument(
            tenantId: 31,
            vehicleId: (int) $vehicle31->id,
            documentType: 'insurance',
            documentNumber: 'INS-31-002',
            issuingAuthority: 'Local Insurer',
            issueDate: now()->subMonths(11)->toDateString(),
            expiryDate: now()->addDays(45)->toDateString(),
            filePath: null,
            notes: null,
            isActive: true,
        ));

        $documentRepository->save(new VehicleDocument(
            tenantId: 31,
            vehicleId: (int) $vehicle31->id,
            documentType: 'permit',
            documentNumber: 'PER-31-001',
            issuingAuthority: 'Authority',
            issueDate: now()->subMonths(11)->toDateString(),
            expiryDate: now()->addDays(5)->toDateString(),
            filePath: null,
            notes: null,
            isActive: false,
        ));

        $documentRepository->save(new VehicleDocument(
            tenantId: 32,
            vehicleId: (int) $vehicle32->id,
            documentType: 'insurance',
            documentNumber: 'INS-32-001',
            issuingAuthority: 'Other Insurer',
            issueDate: now()->subMonths(11)->toDateString(),
            expiryDate: now()->addDays(3)->toDateString(),
            filePath: null,
            notes: null,
            isActive: true,
        ));

        $documents = $documentRepository->listExpiringSoon(31, 30);

        $this->assertCount(1, $documents);
        $this->assertInstanceOf(VehicleDocument::class, $documents[0]);
        $this->assertSame((int) $expiringSoon->id, (int) $documents[0]->id);
        $this->assertSame(31, $documents[0]->tenantId);
    }

    private function makeVehicle(
        int $tenantId,
        int $vehicleTypeId,
        string $registration,
        string $state,
        bool $isRentable,
        bool $isServiceable,
    ): Vehicle {
        return new Vehicle(
            tenantId: $tenantId,
            vehicleTypeId: $vehicleTypeId,
            registrationNumber: $registration,
            make: 'Toyota',
            model: 'Corolla',
            year: 2023,
            ownershipType: 'owned',
            isRentable: $isRentable,
            isServiceable: $isServiceable,
            currentState: $state,
            currentOdometer: '12000.00',
            fuelType: 'petrol',
            transmission: 'automatic',
            seatingCapacity: 5,
            color: 'White',
        );
    }

    private function seedTenant(int $tenantId): void
    {
        if (DB::table('tenants')->where('id', $tenantId)->exists()) {
            return;
        }

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
