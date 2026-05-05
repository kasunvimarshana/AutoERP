<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Tests\TestCase;

class RentalRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $customerId = 1;

    private int $currencyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id'                   => $this->tenantId,
            'name'                 => 'Test Tenant',
            'slug'                 => 'test-tenant',
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
        ]);

        DB::table('currencies')->insert([
            'id'             => $this->currencyId,
            'code'           => 'USD',
            'name'           => 'US Dollar',
            'symbol'         => '$',
            'decimal_places' => 2,
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('users')->insert([
            'id'          => 1,
            'tenant_id'   => $this->tenantId,
            'org_unit_id' => null,
            'first_name'  => 'Test',
            'last_name'   => 'User',
            'email'       => 'test@example.com',
            'password'    => bcrypt('password'),
            'status'      => 'active',
            'preferences' => null,
            'address'     => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('customers')->insert([
            'id'                  => $this->customerId,
            'tenant_id'           => $this->tenantId,
            'user_id'             => null,
            'org_unit_id'         => null,
            'customer_code'       => 'CUST-001',
            'name'                => 'Test Customer',
            'type'                => 'company',
            'tax_number'          => null,
            'registration_number' => null,
            'currency_id'         => $this->currencyId,
            'credit_limit'        => 0,
            'payment_terms_days'  => 30,
            'ar_account_id'       => null,
            'status'              => 'active',
            'notes'               => null,
            'metadata'            => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        DB::table('assets')->insert([
            'id'                   => 1,
            'tenant_id'            => $this->tenantId,
            'org_unit_id'          => null,
            'row_version'          => 1,
            'asset_code'           => 'VH-001',
            'name'                 => 'Test Vehicle',
            'asset_kind'           => 'vehicle',
            'usage_profile'        => 'dual_use',
            'ownership_type'       => 'owned',
            'owner_supplier_id'    => null,
            'registration_number'  => 'TEST-001',
            'vin'                  => null,
            'manufacturer'         => null,
            'model'                => null,
            'model_year'           => null,
            'color'                => null,
            'current_meter_reading' => 0,
            'meter_unit'           => 'km',
            'status'               => 'active',
            'commissioned_on'      => null,
            'retired_on'           => null,
            'metadata'             => null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    // ── save (create) ──────────────────────────────────────────────────────────

    public function test_save_creates_new_booking(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $booking = $this->buildBooking();
        $saved   = $repo->save($booking);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->customerId, $saved->getCustomerId());
        $this->assertSame('with_driver', $saved->getRentalMode());
        $this->assertSame('owned_fleet', $saved->getOwnershipModel());
        $this->assertSame('draft', $saved->getStatus());
        $this->assertSame('daily', $saved->getRatePlan());
        $this->assertEqualsWithDelta(150.0, $saved->getRateAmount(), PHP_FLOAT_EPSILON);
    }

    // ── save (update) ──────────────────────────────────────────────────────────

    public function test_save_updates_existing_booking(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $saved = $repo->save($this->buildBooking());
        $id    = (int) $saved->getId();

        $updated = new RentalBooking(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            rentalMode: 'without_driver',
            ownershipModel: 'third_party',
            pickupAt: '2026-06-01 10:00:00',
            returnDueAt: '2026-06-10 10:00:00',
            currencyId: $this->currencyId,
            ratePlan: 'weekly',
            rateAmount: 800.0,
            status: 'reserved',
            bookingNumber: $saved->getBookingNumber(),
            id: $id,
        );

        $result = $repo->save($updated);

        $this->assertSame($id, $result->getId());
        $this->assertSame('without_driver', $result->getRentalMode());
        $this->assertSame('reserved', $result->getStatus());
        $this->assertEqualsWithDelta(800.0, $result->getRateAmount(), PHP_FLOAT_EPSILON);
    }

    // ── findById ───────────────────────────────────────────────────────────────

    public function test_findById_returns_booking(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $saved = $repo->save($this->buildBooking());
        $id    = (int) $saved->getId();

        $found = $repo->findById($this->tenantId, $id);

        $this->assertNotNull($found);
        $this->assertSame($id, $found->getId());
    }

    public function test_findById_returns_null_for_wrong_tenant(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $saved = $repo->save($this->buildBooking());
        $id    = (int) $saved->getId();

        $this->assertNull($repo->findById(999, $id));
    }

    public function test_findById_returns_null_when_not_found(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $this->assertNull($repo->findById($this->tenantId, 99999));
    }

    // ── findByTenant ───────────────────────────────────────────────────────────

    public function test_findByTenant_returns_all_bookings_for_tenant(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $repo->save($this->buildBooking());
        $repo->save($this->buildBooking());

        $results = $repo->findByTenant($this->tenantId);

        $this->assertCount(2, $results);
        $this->assertSame($this->tenantId, $results[0]->getTenantId());
    }

    public function test_findByTenant_filters_by_status(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $repo->save($this->buildBooking(status: 'draft'));
        $repo->save($this->buildBooking(status: 'reserved'));

        $drafts = $repo->findByTenant($this->tenantId, null, ['status' => 'draft']);
        $this->assertCount(1, $drafts);
        $this->assertSame('draft', $drafts[0]->getStatus());
    }

    public function test_findByTenant_isolates_across_tenants(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $repo->save($this->buildBooking());

        $this->assertCount(0, $repo->findByTenant(999));
    }

    // ── delete ─────────────────────────────────────────────────────────────────

    public function test_delete_removes_booking(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $saved = $repo->save($this->buildBooking());
        $id    = (int) $saved->getId();

        $this->assertTrue($repo->delete($this->tenantId, $id));
        $this->assertNull($repo->findById($this->tenantId, $id));
    }

    public function test_delete_returns_false_for_wrong_tenant(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $saved = $repo->save($this->buildBooking());
        $id    = (int) $saved->getId();

        $this->assertFalse($repo->delete(999, $id));
        $this->assertNotNull($repo->findById($this->tenantId, $id));
    }

    // ── nextBookingNumber ──────────────────────────────────────────────────────

    public function test_nextBookingNumber_generates_sequential_number(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        // With 0 records the first number is ...000001
        $first = $repo->nextBookingNumber($this->tenantId, null);
        $this->assertStringStartsWith('RNT-', $first);
        $this->assertStringEndsWith('000001', $first);

        // Persist a booking so the sequence counter advances to ...000002
        $repo->save($this->buildBooking());

        $second = $repo->nextBookingNumber($this->tenantId, null);
        $this->assertStringEndsWith('000002', $second);
        $this->assertNotEquals($first, $second);
    }

    // ── findConflictingBookings ────────────────────────────────────────────────

    public function test_findConflictingBookings_detects_overlap(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        // Persist a 'reserved' booking and link it to an asset
        $saved = $repo->save($this->buildBooking(status: 'reserved'));
        $id    = (int) $saved->getId();

        DB::table('rental_booking_assets')->insert([
            'tenant_id'         => $this->tenantId,
            'org_unit_id'       => null,
            'row_version'       => 1,
            'rental_booking_id' => $id,
            'asset_id'          => 1,
            'meter_out'         => null,
            'meter_in'          => null,
            'meter_unit'        => 'km',
            'asset_status'      => 'reserved',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Overlapping range
        $conflicts = $repo->findConflictingBookings(
            tenantId: $this->tenantId,
            assetId: 1,
            pickupAt: '2026-06-03 00:00:00',
            returnDueAt: '2026-06-07 00:00:00',
        );

        $this->assertCount(1, $conflicts);
        $this->assertSame($id, $conflicts[0]->getId());
    }

    public function test_findConflictingBookings_excludes_booking_by_id(): void
    {
        /** @var RentalBookingRepositoryInterface $repo */
        $repo = app(RentalBookingRepositoryInterface::class);

        $saved = $repo->save($this->buildBooking(status: 'reserved'));
        $id    = (int) $saved->getId();

        DB::table('rental_booking_assets')->insert([
            'tenant_id'         => $this->tenantId,
            'org_unit_id'       => null,
            'row_version'       => 1,
            'rental_booking_id' => $id,
            'asset_id'          => 1,
            'meter_out'         => null,
            'meter_in'          => null,
            'meter_unit'        => 'km',
            'asset_status'      => 'reserved',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $conflicts = $repo->findConflictingBookings(
            tenantId: $this->tenantId,
            assetId: 1,
            pickupAt: '2026-06-03 00:00:00',
            returnDueAt: '2026-06-07 00:00:00',
            excludeBookingId: $id,
        );

        $this->assertCount(0, $conflicts);
    }

    // ── HELPERS ────────────────────────────────────────────────────────────────

    private static int $bookingCounter = 0;

    private function buildBooking(string $status = 'draft'): RentalBooking
    {
        self::$bookingCounter++;

        return new RentalBooking(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            rentalMode: 'with_driver',
            ownershipModel: 'owned_fleet',
            pickupAt: '2026-06-01 10:00:00',
            returnDueAt: '2026-06-10 10:00:00',
            currencyId: $this->currencyId,
            ratePlan: 'daily',
            rateAmount: 150.0,
            status: $status,
            bookingNumber: 'RNT-TEST-' . str_pad((string) self::$bookingCounter, 4, '0', STR_PAD_LEFT),
        );
    }
}
