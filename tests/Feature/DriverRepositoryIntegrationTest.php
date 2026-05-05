<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\Entities\DriverLicense;
use Modules\Driver\Domain\RepositoryInterfaces\DriverLicenseRepositoryInterface;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;
use Modules\Driver\Domain\ValueObjects\CompensationType;
use Modules\Driver\Domain\ValueObjects\DriverStatus;
use Tests\TestCase;

class DriverRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_repository_saves_updates_and_filters_by_tenant(): void
    {
        $this->seedTenant(51);
        $this->seedTenant(52);

        /** @var DriverRepositoryInterface $repo */
        $repo = app(DriverRepositoryInterface::class);

        // Create driver for tenant 51
        $driver = $repo->save(new Driver(
            id: null,
            tenantId: 51,
            orgUnitId: null,
            employeeId: null,
            driverCode: 'DRV-001',
            fullName: 'John Doe',
            phone: '+94711234567',
            email: 'john@example.com',
            address: '10 Main St',
            compensationType: CompensationType::PerTrip,
            perTripRate: '1500.000000',
            commissionPct: '0.00',
            status: DriverStatus::Available,
            metadata: null,
            isActive: true,
        ));

        $this->assertNotNull($driver->id);
        $this->assertSame('John Doe', $driver->fullName);
        $this->assertSame(DriverStatus::Available, $driver->status);
        $this->assertSame(CompensationType::PerTrip, $driver->compensationType);

        // Create a second driver for tenant 52 (should not appear in tenant 51 queries)
        $repo->save(new Driver(
            id: null,
            tenantId: 52,
            orgUnitId: null,
            employeeId: null,
            driverCode: 'DRV-001',
            fullName: 'Jane Smith',
            phone: null,
            email: null,
            address: null,
            compensationType: CompensationType::Salary,
            perTripRate: '0.000000',
            commissionPct: '0.00',
            status: DriverStatus::OffDuty,
            metadata: null,
            isActive: true,
        ));

        // Tenant isolation
        $tenant51Drivers = $repo->findByTenant(51);
        $tenant52Drivers = $repo->findByTenant(52);

        $this->assertCount(1, $tenant51Drivers);
        $this->assertSame('John Doe', $tenant51Drivers[0]->fullName);
        $this->assertCount(1, $tenant52Drivers);

        // Update: change name and bump row_version
        $driver->fullName = 'John Updated';
        $updated = $repo->save($driver);

        $this->assertSame($driver->id, $updated->id);
        $this->assertSame('John Updated', $updated->fullName);

        $rowVersion = DB::table('fleet_drivers')->where('id', $driver->id)->value('row_version');
        $this->assertSame(2, (int) $rowVersion);

        // Status update
        $repo->updateStatus((int) $driver->id, DriverStatus::OnTrip);
        $refreshed = $repo->findById((int) $driver->id);
        $this->assertSame(DriverStatus::OnTrip, $refreshed->status);

        // Available-for-trip filter (tenant 51 driver is on_trip now, not available)
        $available = $repo->findAvailableForTrip(51);
        $this->assertCount(0, $available);

        // Soft delete
        $repo->delete((int) $driver->id);
        $this->assertNull($repo->findById((int) $driver->id));
    }

    public function test_driver_license_repository_saves_and_finds_expiring_soon(): void
    {
        $this->seedTenant(61);

        /** @var DriverRepositoryInterface $driverRepo */
        $driverRepo = app(DriverRepositoryInterface::class);

        /** @var DriverLicenseRepositoryInterface $licenseRepo */
        $licenseRepo = app(DriverLicenseRepositoryInterface::class);

        // Create a driver
        $driver = $driverRepo->save(new Driver(
            id: null,
            tenantId: 61,
            orgUnitId: null,
            employeeId: null,
            driverCode: 'DRV-601',
            fullName: 'Alice Brown',
            phone: null,
            email: null,
            address: null,
            compensationType: CompensationType::Commission,
            perTripRate: '0.000000',
            commissionPct: '10.00',
            status: DriverStatus::Available,
            metadata: null,
            isActive: true,
        ));

        // License expiring in 10 days (within default 30-day window)
        $soonExpiry = Carbon::today()->addDays(10)->toDateString();
        $license = $licenseRepo->save(new DriverLicense(
            id: null,
            tenantId: 61,
            driverId: (int) $driver->id,
            licenseNumber: 'LIC-ABC-001',
            licenseClass: 'B',
            issuedCountry: 'LKA',
            issueDate: '2020-01-15',
            expiryDate: $soonExpiry,
            filePath: null,
            isActive: true,
        ));

        $this->assertNotNull($license->id);
        $this->assertSame('LIC-ABC-001', $license->licenseNumber);

        // License expiring in 60 days (outside window)
        $licenseRepo->save(new DriverLicense(
            id: null,
            tenantId: 61,
            driverId: (int) $driver->id,
            licenseNumber: 'LIC-ABC-002',
            licenseClass: 'C',
            issuedCountry: 'LKA',
            issueDate: '2020-01-15',
            expiryDate: Carbon::today()->addDays(60)->toDateString(),
            filePath: null,
            isActive: true,
        ));

        // Expiring soon (within 30 days)
        $expiringSoon = $licenseRepo->findExpiringSoon(61, 30);
        $this->assertCount(1, $expiringSoon);
        $this->assertSame('LIC-ABC-001', $expiringSoon[0]->licenseNumber);

        // List by driver
        $all = $licenseRepo->findByDriver((int) $driver->id);
        $this->assertCount(2, $all);

        // Update license
        $license->licenseClass = 'A';
        $updatedLicense = $licenseRepo->save($license);
        $this->assertSame('A', $updatedLicense->licenseClass);

        // Delete
        $licenseRepo->delete((int) $license->id);
        $this->assertNull($licenseRepo->findById((int) $license->id));
    }

    private function seedTenant(int $tenantId): void
    {
        if (DB::table('tenants')->where('id', $tenantId)->exists()) {
            return;
        }

        DB::table('tenants')->insert([
            'id'                    => $tenantId,
            'name'                  => 'Tenant ' . $tenantId,
            'slug'                  => 'tenant-' . $tenantId,
            'domain'                => null,
            'logo_path'             => null,
            'database_config'       => null,
            'mail_config'           => null,
            'cache_config'          => null,
            'queue_config'          => null,
            'feature_flags'         => null,
            'api_keys'              => null,
            'settings'              => null,
            'plan'                  => 'free',
            'tenant_plan_id'        => null,
            'status'                => 'active',
            'active'                => true,
            'trial_ends_at'         => null,
            'subscription_ends_at'  => null,
            'created_at'            => now(),
            'updated_at'            => now(),
            'deleted_at'            => null,
        ]);
    }
}
