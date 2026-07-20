<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Finance\Database\Seeders\FinanceSeeder;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\OrganizationUnit\Constants\OrganizationUnitHierarchy;
use Modules\OrganizationUnit\Database\Seeders\OrganizationUnitSeeder;
use Tests\TestCase;

final class FinanceSeederTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT_INVENTORY = '1200';
    private const ACCOUNT_SUPPLIER_ADVANCE = '1400';
    private const ACCOUNT_PAYABLE = '2100';
    private const ACCOUNT_CUSTOMER_ADVANCE = '2300';
    private const ACCOUNT_CUSTOMER_DEPOSIT = '2350';
    private const ACCOUNT_RENTAL_REVENUE = '4300';
    private const ACCOUNT_RENTAL_EXPENSE = '5300';

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
            FinancePostingProfileCode::InventoryReceipt->value,
            FinancePostingProfileCode::CustomerReceipt->value,
            FinancePostingProfileCode::SupplierPayment->value,
            FinancePostingProfileCode::CustomerAdvance->value,
            FinancePostingProfileCode::SupplierAdvance->value,
            FinancePostingProfileCode::CustomerRentalInvoice->value,
            FinancePostingProfileCode::SupplierRentalInvoice->value,
            FinancePostingProfileCode::RentalDeposit->value,
        ] as $profileCode) {
            $this->assertDatabaseHas('finance_posting_profiles', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'code' => $profileCode,
                'is_active' => true,
            ]);
        }
        $this->assertSame(0, DB::table('finance_posting_profiles')->whereNull('organization_unit_id')->count());

        foreach ([
            self::ACCOUNT_INVENTORY,
            self::ACCOUNT_SUPPLIER_ADVANCE,
            self::ACCOUNT_PAYABLE,
            self::ACCOUNT_CUSTOMER_ADVANCE,
            self::ACCOUNT_CUSTOMER_DEPOSIT,
            self::ACCOUNT_RENTAL_REVENUE,
            self::ACCOUNT_RENTAL_EXPENSE,
        ] as $accountCode) {
            $this->assertGreaterThan(0, $this->accountId($tenantId, $organizationUnitId, $accountCode));
        }

        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::Inventory->value, self::ACCOUNT_INVENTORY);
        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::Payable->value, self::ACCOUNT_PAYABLE);
        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::SupplierAdvance->value, self::ACCOUNT_SUPPLIER_ADVANCE);
        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::CustomerAdvance->value, self::ACCOUNT_CUSTOMER_ADVANCE);
        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::CustomerDeposit->value, self::ACCOUNT_CUSTOMER_DEPOSIT);
        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::RentalRevenue->value, self::ACCOUNT_RENTAL_REVENUE);
        $this->assertActiveAssignment($tenantId, $organizationUnitId, FinanceAccountRoleCode::RentalExpense->value, self::ACCOUNT_RENTAL_EXPENSE);
        $this->assertSame(0, DB::table('finance_account_assignments')->whereNull('organization_unit_id')->count());
    }

    public function test_semantic_payment_withholding_and_rental_profiles_have_complete_role_mappings(): void
    {
        $tenantId = $this->createTenant('AUTOERP');

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->seed(OrganizationUnitSeeder::class);
            $this->seed(FinanceSeeder::class);
        });

        foreach ([
            FinancePostingProfileCode::CustomerReceipt->value => [
                FinanceAccountRoleCode::Cash->value,
                FinanceAccountRoleCode::Bank->value,
                FinanceAccountRoleCode::Receivable->value,
                FinanceAccountRoleCode::CustomerAdvance->value,
            ],
            FinancePostingProfileCode::SupplierPayment->value => [
                FinanceAccountRoleCode::Cash->value,
                FinanceAccountRoleCode::Bank->value,
                FinanceAccountRoleCode::Payable->value,
                FinanceAccountRoleCode::SupplierAdvance->value,
            ],
            FinancePostingProfileCode::CustomerAdvance->value => [
                FinanceAccountRoleCode::Cash->value,
                FinanceAccountRoleCode::Bank->value,
                FinanceAccountRoleCode::Receivable->value,
                FinanceAccountRoleCode::CustomerAdvance->value,
            ],
            FinancePostingProfileCode::SupplierAdvance->value => [
                FinanceAccountRoleCode::Cash->value,
                FinanceAccountRoleCode::Bank->value,
                FinanceAccountRoleCode::Payable->value,
                FinanceAccountRoleCode::SupplierAdvance->value,
            ],
            FinancePostingProfileCode::PurchaseInvoice->value => [
                FinanceAccountRoleCode::Expense->value,
                FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value,
                FinanceAccountRoleCode::Payable->value,
                FinanceAccountRoleCode::TaxReceivable->value,
                FinanceAccountRoleCode::WithholdingPayable->value,
            ],
            FinancePostingProfileCode::CustomerRentalInvoice->value => [
                FinanceAccountRoleCode::Receivable->value,
                FinanceAccountRoleCode::RentalRevenue->value,
                FinanceAccountRoleCode::TaxPayable->value,
                FinanceAccountRoleCode::WithholdingReceivable->value,
            ],
            FinancePostingProfileCode::SupplierRentalInvoice->value => [
                FinanceAccountRoleCode::RentalExpense->value,
                FinanceAccountRoleCode::TaxReceivable->value,
                FinanceAccountRoleCode::Payable->value,
                FinanceAccountRoleCode::WithholdingPayable->value,
            ],
            FinancePostingProfileCode::RentalDeposit->value => [
                FinanceAccountRoleCode::Cash->value,
                FinanceAccountRoleCode::Bank->value,
                FinanceAccountRoleCode::CustomerDeposit->value,
            ],
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
        $inventoryAccountId = $this->accountId($tenantId, $organizationUnitId, self::ACCOUNT_INVENTORY);
        $inventoryRoleId = $this->roleId($tenantId, FinanceAccountRoleCode::Inventory->value);

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
