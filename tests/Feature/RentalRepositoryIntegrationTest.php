<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Domain\Entities\Rental;
use Modules\Rental\Domain\Entities\RentalCharge;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRepositoryInterface;
use Modules\Rental\Domain\ValueObjects\ChargeType;
use Modules\Rental\Domain\ValueObjects\RentalStatus;
use Modules\Rental\Domain\ValueObjects\RentalType;
use Tests\TestCase;

class RentalRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_repository_saves_updates_and_filters_by_tenant(): void
    {
        $this->seedTenant(71);
        $this->seedTenant(72);

        $customerId71 = $this->seedCustomer(71, 'CUST-71-001');
        $customerId72 = $this->seedCustomer(72, 'CUST-72-001');
        $vehicleId71  = $this->seedVehicle(71, 'REG-71-001');
        $vehicleId72  = $this->seedVehicle(72, 'REG-72-001');

        /** @var RentalRepositoryInterface $repo */
        $repo = app(RentalRepositoryInterface::class);

        $rental = $repo->save($this->makeRental(
            tenantId: 71,
            customerId: $customerId71,
            vehicleId: $vehicleId71,
            rentalNumber: 'RNT-71-001',
        ));

        $this->assertNotNull($rental->id);
        $this->assertSame(71, $rental->tenantId);
        $this->assertSame('RNT-71-001', $rental->rentalNumber);
        $this->assertSame(RentalStatus::Pending, $rental->status);
        $this->assertSame(RentalType::SelfDrive, $rental->rentalType);
        $this->assertSame(1, $rental->rowVersion);

        // Create rental for tenant 72 — should not appear in tenant 71 queries
        $repo->save($this->makeRental(
            tenantId: 72,
            customerId: $customerId72,
            vehicleId: $vehicleId72,
            rentalNumber: 'RNT-72-001',
        ));

        // Tenant isolation
        $tenant71Rentals = $repo->findByTenant(71);
        $tenant72Rentals = $repo->findByTenant(72);

        $this->assertCount(1, $tenant71Rentals);
        $this->assertSame('RNT-71-001', $tenant71Rentals[0]->rentalNumber);
        $this->assertCount(1, $tenant72Rentals);
        $this->assertSame('RNT-72-001', $tenant72Rentals[0]->rentalNumber);

        // Find by id with tenant check
        $found = $repo->findById((int) $rental->id, 71);
        $this->assertInstanceOf(Rental::class, $found);
        $this->assertSame((int) $rental->id, (int) $found->id);

        // findById with wrong tenant returns null
        $notFound = $repo->findById((int) $rental->id, 72);
        $this->assertNull($notFound);

        // Status update
        $repo->updateStatus((int) $rental->id, 71, RentalStatus::Confirmed);
        $refreshed = $repo->findById((int) $rental->id, 71);
        $this->assertSame(RentalStatus::Confirmed, $refreshed->status);

        // findActiveByVehicle
        $repo->updateStatus((int) $rental->id, 71, RentalStatus::Active);
        $active = $repo->findActiveByVehicle($vehicleId71, 71);
        $this->assertCount(1, $active);

        // row_version increments on update
        $repo->save($refreshed);
        $rowVersion = DB::table('fleet_rentals')->where('id', $rental->id)->value('row_version');
        $this->assertGreaterThan(1, (int) $rowVersion);

        // Soft delete
        $repo->delete((int) $rental->id, 71);
        $this->assertNull($repo->findById((int) $rental->id, 71));
    }

    public function test_rental_charge_repository_saves_and_finds_by_rental(): void
    {
        $this->seedTenant(73);

        $customerId = $this->seedCustomer(73, 'CUST-73-001');
        $vehicleId  = $this->seedVehicle(73, 'REG-73-001');

        /** @var RentalRepositoryInterface $rentalRepo */
        $rentalRepo = app(RentalRepositoryInterface::class);

        /** @var RentalChargeRepositoryInterface $chargeRepo */
        $chargeRepo = app(RentalChargeRepositoryInterface::class);

        $rental = $rentalRepo->save($this->makeRental(
            tenantId: 73,
            customerId: $customerId,
            vehicleId: $vehicleId,
            rentalNumber: 'RNT-73-001',
        ));

        // Create two charges
        $charge1 = $chargeRepo->save(new RentalCharge(
            id: null,
            tenantId: 73,
            rentalId: (int) $rental->id,
            chargeType: ChargeType::Fuel,
            description: 'Fuel refill',
            quantity: '2.0000',
            unitPrice: '5000.000000',
            amount: '10000.000000',
            isActive: true,
        ));

        $charge2 = $chargeRepo->save(new RentalCharge(
            id: null,
            tenantId: 73,
            rentalId: (int) $rental->id,
            chargeType: ChargeType::Damage,
            description: 'Minor scratch repair',
            quantity: '1.0000',
            unitPrice: '15000.000000',
            amount: '15000.000000',
            isActive: true,
        ));

        $this->assertNotNull($charge1->id);
        $this->assertSame(ChargeType::Fuel, $charge1->chargeType);
        $this->assertSame('10000.000000', $charge1->amount);

        // findByRental
        $charges = $chargeRepo->findByRental((int) $rental->id, 73);
        $this->assertCount(2, $charges);

        // findById
        $found = $chargeRepo->findById((int) $charge1->id, 73);
        $this->assertInstanceOf(RentalCharge::class, $found);
        $this->assertSame((int) $charge1->id, (int) $found->id);

        // Delete charge
        $chargeRepo->delete((int) $charge2->id, 73);
        $remaining = $chargeRepo->findByRental((int) $rental->id, 73);
        $this->assertCount(1, $remaining);
        $this->assertSame((int) $charge1->id, (int) $remaining[0]->id);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeRental(
        int $tenantId,
        int $customerId,
        int $vehicleId,
        string $rentalNumber,
    ): Rental {
        return new Rental(
            id: null,
            tenantId: $tenantId,
            orgUnitId: null,
            customerId: $customerId,
            vehicleId: $vehicleId,
            driverId: null,
            rentalNumber: $rentalNumber,
            rentalType: RentalType::SelfDrive,
            status: RentalStatus::Pending,
            pickupLocation: 'Main Branch',
            returnLocation: 'Main Branch',
            scheduledStartAt: new DateTimeImmutable('2025-07-01 08:00:00'),
            scheduledEndAt: new DateTimeImmutable('2025-07-04 08:00:00'),
            actualStartAt: null,
            actualEndAt: null,
            startOdometer: null,
            endOdometer: null,
            ratePerDay: '8000.000000',
            estimatedDays: '3.0000',
            actualDays: null,
            subtotal: '24000.000000',
            discountAmount: '0.000000',
            taxAmount: '0.000000',
            totalAmount: '24000.000000',
            depositAmount: '5000.000000',
            notes: null,
            cancelledAt: null,
            cancellationReason: null,
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

    private function seedCustomer(int $tenantId, string $customerCode): int
    {
        return DB::table('customers')->insertGetId([
            'tenant_id'          => $tenantId,
            'org_unit_id'        => null,
            'user_id'            => null,
            'customer_code'      => $customerCode,
            'name'               => 'Customer ' . $customerCode,
            'type'               => 'individual',
            'tax_number'         => null,
            'registration_number'=> null,
            'currency_id'        => null,
            'credit_limit'       => '0.000000',
            'payment_terms_days' => 30,
            'ar_account_id'      => null,
            'status'             => 'active',
            'notes'              => null,
            'metadata'           => null,
            'created_at'         => now(),
            'updated_at'         => now(),
            'deleted_at'         => null,
        ]);
    }

    private function seedVehicle(int $tenantId, string $registration): int
    {
        $typeId = DB::table('fleet_vehicle_types')->insertGetId([
            'tenant_id'        => $tenantId,
            'name'             => 'Sedan',
            'description'      => null,
            'base_daily_rate'  => '8000.000000',
            'base_hourly_rate' => '600.000000',
            'seating_capacity' => 5,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
            'deleted_at'       => null,
        ]);

        return DB::table('fleet_vehicles')->insertGetId([
            'tenant_id'           => $tenantId,
            'vehicle_type_id'     => $typeId,
            'registration_number' => $registration,
            'make'                => 'Toyota',
            'model'               => 'Corolla',
            'year'                => 2023,
            'ownership_type'      => 'owned',
            'is_rentable'         => true,
            'is_serviceable'      => true,
            'current_state'       => 'available',
            'current_odometer'    => '10000.00',
            'fuel_type'           => 'petrol',
            'transmission'        => 'automatic',
            'seating_capacity'    => 5,
            'color'               => 'White',
            'is_active'           => true,
            'created_at'          => now(),
            'updated_at'          => now(),
            'deleted_at'          => null,
        ]);
    }
}
