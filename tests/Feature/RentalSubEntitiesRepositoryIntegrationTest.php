<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Domain\Entities\RentalDeposit;
use Modules\Rental\Domain\Entities\RentalDriverAssignment;
use Modules\Rental\Domain\Entities\RentalIncident;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;
use Tests\TestCase;

class RentalSubEntitiesRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;
    private int $bookingId = 1;
    private int $assetId = 1;
    private int $employeeId = 1;
    private int $currencyId = 1;
    private int $customerId = 1;
    private int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id' => $this->tenantId,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
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
        ]);

        DB::table('currencies')->insert([
            'id' => $this->currencyId,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => $this->customerId,
            'tenant_id' => $this->tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUST-001',
            'name' => 'Test Customer',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => $this->currencyId,
            'credit_limit' => 0,
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->insert([
            'id' => $this->employeeId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'user_id' => $this->userId,
            'employee_code' => 'EMP-001',
            'job_title' => 'Driver',
            'hire_date' => '2020-01-01',
            'termination_date' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('assets')->insert([
            'id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'asset_code' => 'VH-001',
            'name' => 'Test Vehicle',
            'asset_kind' => 'vehicle',
            'usage_profile' => 'dual_use',
            'ownership_type' => 'owned',
            'owner_supplier_id' => null,
            'registration_number' => 'TEST-001',
            'vin' => null,
            'manufacturer' => null,
            'model' => null,
            'model_year' => null,
            'color' => null,
            'current_meter_reading' => 0,
            'meter_unit' => 'km',
            'status' => 'active',
            'commissioned_on' => null,
            'retired_on' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second user for second employee
        DB::table('users')->insert([
            'id' => $this->userId + 1,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'first_name' => 'Second',
            'last_name' => 'Employee',
            'email' => 'employee2@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second employee (used in filter test)
        DB::table('employees')->insert([
            'id' => $this->employeeId + 1,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'user_id' => $this->userId + 1,
            'employee_code' => 'EMP-002',
            'job_title' => 'Driver',
            'hire_date' => '2020-01-01',
            'termination_date' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second tenant (used in deposit tenant-scope test)
        DB::table('tenants')->insert([
            'id' => $this->tenantId + 1,
            'name' => 'Second Tenant',
            'slug' => 'second-tenant',
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
        ]);

        DB::table('rental_bookings')->insert([
            'id' => $this->bookingId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'booking_number' => 'BK-TEST-001',
            'customer_id' => $this->customerId,
            'rental_mode' => 'without_driver',
            'ownership_model' => 'owned_fleet',
            'status' => 'draft',
            'pickup_at' => now()->addDay(),
            'return_due_at' => now()->addDays(5),
            'actual_return_at' => null,
            'pickup_location' => null,
            'return_location' => null,
            'currency_id' => $this->currencyId,
            'rate_plan' => 'daily',
            'rate_amount' => 0,
            'estimated_amount' => 0,
            'final_amount' => 0,
            'security_deposit_amount' => 0,
            'security_deposit_status' => 'not_required',
            'partner_supplier_id' => null,
            'terms_and_conditions' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second booking (used in incident findByBooking isolation test)
        DB::table('rental_bookings')->insert([
            'id' => $this->bookingId + 1,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'booking_number' => 'BK-TEST-002',
            'customer_id' => $this->customerId,
            'rental_mode' => 'without_driver',
            'ownership_model' => 'owned_fleet',
            'status' => 'draft',
            'pickup_at' => now()->addDay(),
            'return_due_at' => now()->addDays(5),
            'actual_return_at' => null,
            'pickup_location' => null,
            'return_location' => null,
            'currency_id' => $this->currencyId,
            'rate_plan' => 'daily',
            'rate_amount' => 0,
            'estimated_amount' => 0,
            'final_amount' => 0,
            'security_deposit_amount' => 0,
            'security_deposit_status' => 'not_required',
            'partner_supplier_id' => null,
            'terms_and_conditions' => null,
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ─── Driver Assignment ─────────────────────────────────────────────────────

    public function test_driver_assignment_can_be_saved_and_retrieved(): void
    {
        /** @var RentalDriverAssignmentRepositoryInterface $repo */
        $repo = app(RentalDriverAssignmentRepositoryInterface::class);

        $assignment = new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId,
            assignmentStatus: 'assigned',
            orgUnitId: null,
            assignedFrom: '2025-06-01 08:00:00',
            assignedTo: '2025-06-05 18:00:00',
        );

        $saved = $repo->save($assignment);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->bookingId, $saved->getRentalBookingId());
        $this->assertSame($this->employeeId, $saved->getEmployeeId());
        $this->assertSame('assigned', $saved->getAssignmentStatus());
        $this->assertTrue($saved->isActive());
    }

    public function test_driver_assignment_findById_returns_null_for_wrong_tenant(): void
    {
        /** @var RentalDriverAssignmentRepositoryInterface $repo */
        $repo = app(RentalDriverAssignmentRepositoryInterface::class);

        $saved = $repo->save(new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId,
            assignmentStatus: 'assigned',
        ));

        $this->assertNull($repo->findById(999, (int) $saved->getId()));
    }

    public function test_driver_assignment_findByBooking_filters_by_status(): void
    {
        /** @var RentalDriverAssignmentRepositoryInterface $repo */
        $repo = app(RentalDriverAssignmentRepositoryInterface::class);

        $repo->save(new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId,
            assignmentStatus: 'assigned',
        ));

        $repo->save(new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId + 1,
            assignmentStatus: 'replaced',
        ));

        $active = $repo->findByBooking($this->tenantId, $this->bookingId, 'assigned');
        $this->assertCount(1, $active);
        $this->assertSame('assigned', $active[0]->getAssignmentStatus());

        $all = $repo->findByBooking($this->tenantId, $this->bookingId);
        $this->assertCount(2, $all);
    }

    public function test_driver_assignment_findActiveByEmployee(): void
    {
        /** @var RentalDriverAssignmentRepositoryInterface $repo */
        $repo = app(RentalDriverAssignmentRepositoryInterface::class);

        $repo->save(new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId,
            assignmentStatus: 'assigned',
        ));

        $results = $repo->findActiveByEmployee($this->tenantId, $this->employeeId);
        $this->assertCount(1, $results);
    }

    public function test_driver_assignment_can_be_updated(): void
    {
        /** @var RentalDriverAssignmentRepositoryInterface $repo */
        $repo = app(RentalDriverAssignmentRepositoryInterface::class);

        $saved = $repo->save(new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId,
            assignmentStatus: 'assigned',
        ));

        $updated = new RentalDriverAssignment(
            tenantId: $saved->getTenantId(),
            rentalBookingId: $saved->getRentalBookingId(),
            employeeId: $saved->getEmployeeId(),
            assignmentStatus: 'replaced',
            rowVersion: $saved->getRowVersion() + 1,
            id: $saved->getId(),
        );

        $result = $repo->save($updated);
        $this->assertSame('replaced', $result->getAssignmentStatus());
        $this->assertFalse($result->isActive());
    }

    public function test_driver_assignment_can_be_soft_deleted(): void
    {
        /** @var RentalDriverAssignmentRepositoryInterface $repo */
        $repo = app(RentalDriverAssignmentRepositoryInterface::class);

        $saved = $repo->save(new RentalDriverAssignment(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            employeeId: $this->employeeId,
            assignmentStatus: 'assigned',
        ));

        $this->assertTrue($repo->delete($this->tenantId, (int) $saved->getId()));
        $this->assertNull($repo->findById($this->tenantId, (int) $saved->getId()));
    }

    // ─── Incidents ─────────────────────────────────────────────────────────────

    public function test_incident_can_be_saved_and_retrieved(): void
    {
        /** @var RentalIncidentRepositoryInterface $repo */
        $repo = app(RentalIncidentRepositoryInterface::class);

        $incident = new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            assetId: $this->assetId,
            incidentType: 'damage',
            status: 'open',
            orgUnitId: null,
            occurredAt: '2025-06-03 14:00:00',
            estimatedCost: 250.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
        );

        $saved = $repo->save($incident);

        $this->assertNotNull($saved->getId());
        $this->assertSame('damage', $saved->getIncidentType());
        $this->assertSame('open', $saved->getStatus());
        $this->assertEqualsWithDelta(250.0, $saved->getEstimatedCost(), PHP_FLOAT_EPSILON);
        $this->assertSame('none', $saved->getRecoveryStatus());
    }

    public function test_incident_findByBooking_returns_incidents_for_booking(): void
    {
        /** @var RentalIncidentRepositoryInterface $repo */
        $repo = app(RentalIncidentRepositoryInterface::class);

        $repo->save(new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            assetId: $this->assetId,
            incidentType: 'damage',
            status: 'open',
            estimatedCost: 100.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
        ));

        $repo->save(new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId + 1,
            assetId: $this->assetId,
            incidentType: 'late_return',
            status: 'open',
            estimatedCost: 50.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
        ));

        $incidents = $repo->findByBooking($this->tenantId, $this->bookingId);
        $this->assertCount(1, $incidents);
        $this->assertSame('damage', $incidents[0]->getIncidentType());
    }

    public function test_incident_findByTenant_filters_by_status(): void
    {
        /** @var RentalIncidentRepositoryInterface $repo */
        $repo = app(RentalIncidentRepositoryInterface::class);

        $repo->save(new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            assetId: $this->assetId,
            incidentType: 'damage',
            status: 'open',
            estimatedCost: 100.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
        ));

        $repo->save(new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            assetId: $this->assetId,
            incidentType: 'traffic_violation',
            status: 'resolved',
            estimatedCost: 200.0,
            recoveredAmount: 200.0,
            recoveryStatus: 'full',
        ));

        $open = $repo->findByTenant($this->tenantId, null, ['status' => 'open']);
        $this->assertCount(1, $open);
        $this->assertSame('open', $open[0]->getStatus());

        $all = $repo->findByTenant($this->tenantId);
        $this->assertCount(2, $all);
    }

    public function test_incident_can_be_updated(): void
    {
        /** @var RentalIncidentRepositoryInterface $repo */
        $repo = app(RentalIncidentRepositoryInterface::class);

        $saved = $repo->save(new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            assetId: $this->assetId,
            incidentType: 'damage',
            status: 'open',
            estimatedCost: 500.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
        ));

        $updated = new RentalIncident(
            tenantId: $saved->getTenantId(),
            rentalBookingId: $saved->getRentalBookingId(),
            assetId: $saved->getAssetId(),
            incidentType: $saved->getIncidentType(),
            status: 'resolved',
            estimatedCost: $saved->getEstimatedCost(),
            recoveredAmount: 500.0,
            recoveryStatus: 'full',
            rowVersion: $saved->getRowVersion() + 1,
            id: $saved->getId(),
        );

        $result = $repo->save($updated);
        $this->assertSame('resolved', $result->getStatus());
        $this->assertEqualsWithDelta(500.0, $result->getRecoveredAmount(), PHP_FLOAT_EPSILON);
        $this->assertSame('full', $result->getRecoveryStatus());
    }

    public function test_incident_can_be_soft_deleted(): void
    {
        /** @var RentalIncidentRepositoryInterface $repo */
        $repo = app(RentalIncidentRepositoryInterface::class);

        $saved = $repo->save(new RentalIncident(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            assetId: $this->assetId,
            incidentType: 'damage',
            status: 'open',
            estimatedCost: 100.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
        ));

        $this->assertTrue($repo->delete($this->tenantId, (int) $saved->getId()));
        $this->assertNull($repo->findById($this->tenantId, (int) $saved->getId()));
    }

    // ─── Deposits ──────────────────────────────────────────────────────────────

    public function test_deposit_can_be_saved_and_retrieved(): void
    {
        /** @var RentalDepositRepositoryInterface $repo */
        $repo = app(RentalDepositRepositoryInterface::class);

        $deposit = new RentalDeposit(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            currencyId: $this->currencyId,
            heldAmount: 1000.0,
            status: 'held',
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
            heldAt: '2025-06-01 08:00:00',
        );

        $saved = $repo->save($deposit);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame('held', $saved->getStatus());
        $this->assertEqualsWithDelta(1000.0, $saved->getHeldAmount(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $saved->getReleasedAmount(), PHP_FLOAT_EPSILON);
    }

    public function test_deposit_findByBooking_scopes_by_tenant(): void
    {
        /** @var RentalDepositRepositoryInterface $repo */
        $repo = app(RentalDepositRepositoryInterface::class);

        $repo->save(new RentalDeposit(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            currencyId: $this->currencyId,
            heldAmount: 500.0,
            status: 'held',
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
        ));

        // Different tenant — should not appear
        $repo->save(new RentalDeposit(
            tenantId: $this->tenantId + 1,
            rentalBookingId: $this->bookingId,
            currencyId: $this->currencyId,
            heldAmount: 800.0,
            status: 'held',
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
        ));

        $deposits = $repo->findByBooking($this->tenantId, $this->bookingId);
        $this->assertCount(1, $deposits);
        $this->assertSame($this->tenantId, $deposits[0]->getTenantId());
    }

    public function test_deposit_can_be_released_partially(): void
    {
        /** @var RentalDepositRepositoryInterface $repo */
        $repo = app(RentalDepositRepositoryInterface::class);

        $saved = $repo->save(new RentalDeposit(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            currencyId: $this->currencyId,
            heldAmount: 1000.0,
            status: 'held',
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
        ));

        $released = new RentalDeposit(
            tenantId: $saved->getTenantId(),
            rentalBookingId: $saved->getRentalBookingId(),
            currencyId: $saved->getCurrencyId(),
            heldAmount: $saved->getHeldAmount(),
            status: 'partially_released',
            releasedAmount: 700.0,
            forfeitedAmount: 300.0,
            heldAt: $saved->getHeldAt(),
            releasedAt: '2025-06-10 12:00:00',
            rowVersion: $saved->getRowVersion() + 1,
            id: $saved->getId(),
        );

        $result = $repo->save($released);
        $this->assertSame('partially_released', $result->getStatus());
        $this->assertEqualsWithDelta(700.0, $result->getReleasedAmount(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(300.0, $result->getForfeitedAmount(), PHP_FLOAT_EPSILON);
    }

    public function test_deposit_findById_returns_null_for_wrong_tenant(): void
    {
        /** @var RentalDepositRepositoryInterface $repo */
        $repo = app(RentalDepositRepositoryInterface::class);

        $saved = $repo->save(new RentalDeposit(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            currencyId: $this->currencyId,
            heldAmount: 500.0,
            status: 'held',
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
        ));

        $this->assertNull($repo->findById(999, (int) $saved->getId()));
    }

    public function test_deposit_can_be_soft_deleted(): void
    {
        /** @var RentalDepositRepositoryInterface $repo */
        $repo = app(RentalDepositRepositoryInterface::class);

        $saved = $repo->save(new RentalDeposit(
            tenantId: $this->tenantId,
            rentalBookingId: $this->bookingId,
            currencyId: $this->currencyId,
            heldAmount: 500.0,
            status: 'held',
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
        ));

        $this->assertTrue($repo->delete($this->tenantId, (int) $saved->getId()));
        $this->assertNull($repo->findById($this->tenantId, (int) $saved->getId()));
    }
}
