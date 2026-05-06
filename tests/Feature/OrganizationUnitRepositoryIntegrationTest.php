<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Tests\TestCase;

class OrganizationUnitRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_persists_extended_schema_fields(): void
    {
        $tenantId = 9101;

        $this->insertTenant($tenantId);
        $this->insertWarehouse($tenantId, 9201);

        $revenueAccountId = $this->insertAccount($tenantId, 9301, '4000', 'Revenue Account', 'revenue', 'credit');
        $expenseAccountId = $this->insertAccount($tenantId, 9302, '5000', 'Expense Account', 'expense', 'debit');
        $assetAccountId = $this->insertAccount($tenantId, 9303, '1000', 'Asset Account', 'asset', 'debit');
        $liabilityAccountId = $this->insertAccount($tenantId, 9304, '2000', 'Liability Account', 'liability', 'credit');

        /** @var OrganizationUnitRepositoryInterface $repository */
        $repository = app(OrganizationUnitRepositoryInterface::class);

        $saved = $repository->save(new OrganizationUnit(
            tenantId: $tenantId,
            name: 'Integration Unit',
            typeId: null,
            parentId: null,
            managerUserId: null,
            code: 'OU-INTEG',
            path: 'OU-INTEG',
            depth: 0,
            metadata: ['source' => 'integration-test'],
            isActive: true,
            description: 'Organization unit persistence test',
            imagePath: 'org-units/integration-unit.png',
            defaultRevenueAccountId: $revenueAccountId,
            defaultExpenseAccountId: $expenseAccountId,
            defaultAssetAccountId: $assetAccountId,
            defaultLiabilityAccountId: $liabilityAccountId,
            warehouseId: 9201,
            left: 1,
            right: 2,
        ));

        $this->assertNotNull($saved->getId());

        $row = DB::table('org_units')->where('id', $saved->getId())->first();

        $this->assertNotNull($row);
        $this->assertSame('org-units/integration-unit.png', $row->image_path);
        $this->assertSame($revenueAccountId, (int) $row->default_revenue_account_id);
        $this->assertSame($expenseAccountId, (int) $row->default_expense_account_id);
        $this->assertSame($assetAccountId, (int) $row->default_asset_account_id);
        $this->assertSame($liabilityAccountId, (int) $row->default_liability_account_id);
        $this->assertSame(9201, (int) $row->warehouse_id);
    }

    private function insertTenant(int $tenantId): void
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

    private function insertWarehouse(int $tenantId, int $warehouseId): void
    {
        DB::table('warehouses')->insert([
            'id' => $warehouseId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'image_path' => null,
            'type' => 'standard',
            'address_id' => null,
            'is_active' => true,
            'is_default' => false,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertAccount(
        int $tenantId,
        int $accountId,
        string $code,
        string $name,
        string $type,
        string $normalBalance,
    ): int {
        DB::table('accounts')->insert([
            'id' => $accountId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'parent_id' => null,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'sub_type' => null,
            'normal_balance' => $normalBalance,
            'is_system' => false,
            'is_bank_account' => false,
            'is_credit_card' => false,
            'currency_id' => null,
            'description' => null,
            'is_active' => true,
            'path' => null,
            'depth' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        return $accountId;
    }
}
