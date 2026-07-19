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

    private const PROFILE_CUSTOMER_RECEIPT = 'customer_receipt';
    private const PROFILE_SUPPLIER_PAYMENT = 'supplier_payment';
    private const PROFILE_CUSTOMER_ADVANCE = 'customer_advance';
    private const PROFILE_SUPPLIER_ADVANCE = 'supplier_advance';
    private const PROFILE_PURCHASE_INVOICE = 'purchase_invoice';

    public function test_default_posting_profiles_are_seeded_for_the_protected_root_organization_unit(): void
    {
        $tenantId = $this->createTenant('AUTOERP');

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(OrganizationUnitSeeder::class);
            $this->seed(FinanceSeeder::class);
        });

        $organizationUnitId = $this->rootOrganizationUnitId($tenantId);
        self::assertGreaterThan(0, $organizationUnitId);

        foreach ([
            'inventory_receipt',
            self::PROFILE_CUSTOMER_RECEIPT,
            self::PROFILE_SUPPLIER_PAYMENT,
            self::PROFILE_CUSTOMER_ADVANCE,
            self::PROFILE_SUPPLIER_ADVANCE,
        ] as $profileCode) {
            $this->assertDatabaseHas('finance_posting_profiles', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'code' => $profileCode,
                'is_active' => true,
            ]);
        }
        $this->assertSame(0, DB::table('finance_posting_profiles')->whereNull('organization_unit_id')->count());

        foreach (['1200', '1400', '2100', '2300'] as $accountCode) {
            $this->assertGreaterThan(0, $this->accountId($tenantId, $organizationUnitId, $accountCode));
        }

        $this->assertActiveAssignment($tenantId, $organizationUnitId, 'inventory', '1200');
        $this->assertActiveAssignment($tenantId, $organizationUnitId, 'payable', '2100');
        $this->assertActiveAssignment($tenantId, $organizationUnitId, 'supplier_advance', '1400');
        $this->assertActiveAssignment($tenantId, $organizationUnitId, 'customer_advance', '2300');
        $this->assertSame(0, DB::table('finance_account_assignments')->whereNull('organization_unit_id')->count());
    }

    public function test_semantic_payment_and_withholding_profiles_have_complete_role_mappings(): void
    {
        $tenantId = $this->createTenant('AUTOERP');

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(OrganizationUnitSeeder::class);
            $this->seed(FinanceSeeder::class);
        });

        foreach ([
            self::PROFILE_CUSTOMER_RECEIPT => ['cash', 'bank', 'receivable', 'customer_advance'],
            self::PROFILE_SUPPLIER_PAYMENT => ['cash', 'bank', 'payable', 'supplier_advance'],
            self::PROFILE_CUSTOMER_ADVANCE => ['cash', 'bank', 'receivable', 'customer_advance'],
            self::PROFILE_SUPPLIER_ADVANCE => ['cash', 'bank', 'payable', 'supplier_advance'],
            self::PROFILE_PURCHASE_INVOICE => ['expense', 'goods_received_not_invoiced', 'payable', 'tax_receivable', 'withholding_payable'],
        ] as $profileCode => $lineKeys) {
            $profileId = (int) DB::table('finance_posting_profiles')
                ->where('tenant_id', $tenantId)
                ->where('code', $profileCode)
                ->value('id');
            $this->assertGreaterThan(0, $profileId);

            $actualLineKeys = DB::table('finance_posting_profile_rules')
                ->where('tenant_id', $tenantId)
                ->where('posting_profile_id', $profileId)
                ->where('is_active', true)
                ->orderBy('line_key')
                ->pluck('line_key')
                ->all();
            sort($lineKeys);
            $this->assertSame($lineKeys, $actualLineKeys, 'Incomplete posting profile '.$profileCode.'.');
        }
    }

    public function test_default_account_assignment_seeding_collapses_exact_duplicate_active_rows(): void
    {
        $tenantId = $this->createTenant('AUTOERP');

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(OrganizationUnitSeeder::class);
            $this->seed(FinanceSeeder::class);
        });

        $organizationUnitId = $this->rootOrganizationUnitId($tenantId);
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

    private function assertActiveAssignment(
        int $tenantId,
        int $organizationUnitId,
        string $roleCode,
        string $accountCode,
    ): void {
        $this->assertDatabaseHas('finance_account_assignments', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $this->roleId($tenantId, $roleCode),
            'account_id' => $this->accountId($tenantId, $organizationUnitId, $accountCode),
            'is_active' => true,
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

    private function rootOrganizationUnitId(int $tenantId): int
    {
        return (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('root_marker', OrganizationUnitHierarchy::ROOT_MARKER)
            ->value('id');
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
