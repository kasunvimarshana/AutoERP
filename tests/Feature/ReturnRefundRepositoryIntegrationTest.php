<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\ReturnRefund\Domain\Entities\ReturnRefund;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnRefundRepositoryInterface;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;
use Tests\TestCase;

class ReturnRefundRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_refund_repository_saves_updates_and_filters_by_tenant(): void
    {
        $this->seedTenant(91);
        $this->seedTenant(92);

        $rentalId91 = $this->seedRental(91, 'RNT-91-001');
        $rentalId92 = $this->seedRental(92, 'RNT-92-001');

        /** @var ReturnRefundRepositoryInterface $repo */
        $repo = app(ReturnRefundRepositoryInterface::class);

        $return1 = $repo->save($this->makeReturnRefund(
            tenantId: 91,
            rentalId: $rentalId91,
            returnNumber: 'RET-91-001',
        ));

        $this->assertNotNull($return1->id);
        $this->assertSame(91, $return1->tenantId);
        $this->assertSame('RET-91-001', $return1->returnNumber);
        $this->assertSame(ReturnStatus::Pending, $return1->status);
        $this->assertSame(1, $return1->rowVersion);
        $this->assertSame('24000.000000', $return1->rentalCharge);

        // Tenant 92
        $repo->save($this->makeReturnRefund(
            tenantId: 92,
            rentalId: $rentalId92,
            returnNumber: 'RET-92-001',
        ));

        // Tenant isolation
        $tenant91 = $repo->findByTenant(91);
        $tenant92 = $repo->findByTenant(92);
        $this->assertCount(1, $tenant91);
        $this->assertSame('RET-91-001', $tenant91[0]->returnNumber);
        $this->assertCount(1, $tenant92);
        $this->assertSame('RET-92-001', $tenant92[0]->returnNumber);

        // findById
        $found = $repo->findById((int) $return1->id, 91);
        $this->assertInstanceOf(ReturnRefund::class, $found);
        $this->assertSame((int) $return1->id, (int) $found->id);

        // findById with wrong tenant returns null
        $this->assertNull($repo->findById((int) $return1->id, 92));

        // findByRental
        $byRental = $repo->findByRental($rentalId91, 91);
        $this->assertCount(1, $byRental);
        $this->assertSame('RET-91-001', $byRental[0]->returnNumber);

        // Status update
        $repo->updateStatus((int) $return1->id, 91, ReturnStatus::Inspected);
        $refreshed = $repo->findById((int) $return1->id, 91);
        $this->assertSame(ReturnStatus::Inspected, $refreshed->status);

        // row_version increments on update
        $repo->save($refreshed);
        $rowVersion = DB::table('fleet_return_refunds')->where('id', $return1->id)->value('row_version');
        $this->assertGreaterThan(1, (int) $rowVersion);

        // Soft delete
        $repo->delete((int) $return1->id, 91);
        $this->assertNull($repo->findById((int) $return1->id, 91));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeReturnRefund(
        int $tenantId,
        int $rentalId,
        string $returnNumber,
    ): ReturnRefund {
        return new ReturnRefund(
            id: null,
            tenantId: $tenantId,
            orgUnitId: null,
            rentalId: $rentalId,
            returnNumber: $returnNumber,
            status: ReturnStatus::Pending,
            returnedAt: new DateTimeImmutable('2025-07-04 10:00:00'),
            endOdometer: '10350.50',
            actualDays: '3.0000',
            rentalCharge: '24000.000000',
            extraCharges: '0.000000',
            damageCharges: '0.000000',
            fuelCharges: '500.000000',
            depositPaid: '5000.000000',
            refundAmount: '4500.000000',
            refundMethod: 'bank_transfer',
            inspectionNotes: null,
            notes: null,
            damagePhotos: null,
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

    private function seedRental(int $tenantId, string $rentalNumber): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'tenant_id'           => $tenantId,
            'org_unit_id'         => null,
            'user_id'             => null,
            'customer_code'       => 'CUST-' . $rentalNumber,
            'name'                => 'Customer ' . $rentalNumber,
            'type'                => 'individual',
            'tax_number'          => null,
            'registration_number' => null,
            'currency_id'         => null,
            'credit_limit'        => '0.000000',
            'payment_terms_days'  => 30,
            'ar_account_id'       => null,
            'status'              => 'active',
            'notes'               => null,
            'metadata'            => null,
            'created_at'          => now(),
            'updated_at'          => now(),
            'deleted_at'          => null,
        ]);

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

        $vehicleId = DB::table('fleet_vehicles')->insertGetId([
            'tenant_id'           => $tenantId,
            'vehicle_type_id'     => $typeId,
            'registration_number' => 'REG-' . $rentalNumber,
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

        return DB::table('fleet_rentals')->insertGetId([
            'tenant_id'          => $tenantId,
            'org_unit_id'        => null,
            'row_version'        => 1,
            'customer_id'        => $customerId,
            'vehicle_id'         => $vehicleId,
            'driver_id'          => null,
            'rental_number'      => $rentalNumber,
            'rental_type'        => 'self_drive',
            'status'             => 'completed',
            'pickup_location'    => 'Main Branch',
            'return_location'    => 'Main Branch',
            'scheduled_start_at' => '2025-07-01 08:00:00',
            'scheduled_end_at'   => '2025-07-04 08:00:00',
            'actual_start_at'    => '2025-07-01 08:30:00',
            'actual_end_at'      => '2025-07-04 09:00:00',
            'start_odometer'     => '10000.00',
            'end_odometer'       => '10350.50',
            'rate_per_day'       => '8000.000000',
            'estimated_days'     => '3.0000',
            'actual_days'        => '3.0208',
            'subtotal'           => '24000.000000',
            'discount_amount'    => '0.000000',
            'tax_amount'         => '0.000000',
            'total_amount'       => '24000.000000',
            'deposit_amount'     => '5000.000000',
            'notes'              => null,
            'cancelled_at'       => null,
            'cancellation_reason'=> null,
            'metadata'           => null,
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
            'deleted_at'         => null,
        ]);
    }
}
