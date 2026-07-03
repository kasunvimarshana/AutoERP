<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Finance\Database\Seeders\FinanceSeeder;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\OrganizationUnit\Database\Seeders\OrganizationUnitSeeder;
use Tests\TestCase;

final class FinanceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_posting_profiles_are_seeded_for_the_protected_root_organization_unit(): void
    {
        $tenantId = $this->createTenant('AUTOERP');

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(OrganizationUnitSeeder::class);
            $this->seed(FinanceSeeder::class);
        });

        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->value('id');

        self::assertGreaterThan(0, $organizationUnitId);
        $this->assertDatabaseHas('finance_posting_profiles', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'inventory_receipt',
            'is_active' => true,
        ]);
        $this->assertSame(0, DB::table('finance_posting_profiles')->whereNull('organization_unit_id')->count());

        $inventoryAccountId = $this->accountId($tenantId, $organizationUnitId, '1200');
        $payableAccountId = $this->accountId($tenantId, $organizationUnitId, '2100');
        $inventoryRoleId = $this->roleId($tenantId, 'inventory');
        $payableRoleId = $this->roleId($tenantId, 'payable');

        $this->assertDatabaseHas('finance_account_assignments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $inventoryRoleId,
            'account_id' => $inventoryAccountId,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('finance_account_assignments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $payableRoleId,
            'account_id' => $payableAccountId,
            'is_active' => true,
        ]);
        $this->assertSame(0, DB::table('finance_account_assignments')->whereNull('organization_unit_id')->count());
    }

    public function test_default_account_assignment_seeding_collapses_exact_duplicate_active_rows(): void
    {
        $tenantId = $this->createTenant('AUTOERP');

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(OrganizationUnitSeeder::class);
            $this->seed(FinanceSeeder::class);
        });

        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->value('id');
        $inventoryAccountId = $this->accountId($tenantId, $organizationUnitId, '1200');
        $inventoryRoleId = $this->roleId($tenantId, 'inventory');

        $duplicateId = (int) DB::table('finance_account_assignments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $inventoryRoleId,
            'account_id' => $inventoryAccountId,
            'effective_from' => '1900-01-01',
            'effective_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(FinanceSeeder::class);
        });

        $activeAssignmentIds = DB::table('finance_account_assignments')
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('account_role_id', $inventoryRoleId)
            ->where('account_id', $inventoryAccountId)
            ->whereDate('effective_from', '1900-01-01')
            ->whereNull('effective_to')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertCount(1, $activeAssignmentIds);
        $this->assertNotContains($duplicateId, $activeAssignmentIds);
        $this->assertDatabaseHas('finance_account_assignments', [
            'id' => $duplicateId,
            'is_active' => false,
        ]);
    }

    private function createTenant(string $code): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'AutoERP',
            'slug' => Str::slug($code),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountId(int $tenantId, int $organizationUnitId, string $code): int
    {
        return (int) DB::table('finance_accounts')
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('code', $code)
            ->value('id');
    }

    private function roleId(int $tenantId, string $code): int
    {
        return (int) DB::table('finance_account_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
    }
}
