<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Tests\TestCase;

class OrganizationUnitDeleteTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_org_unit_rejects_cross_tenant_id(): void
    {
        $this->seedTenants();
        $this->seedOrgUnits();

        /** @var DeleteOrganizationUnitServiceInterface $service */
        $service = app(DeleteOrganizationUnitServiceInterface::class);

        $deleted = $service->execute([
            'id' => 8201,
            'tenant_id' => 11,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('org_units', [
            'id' => 8201,
            'tenant_id' => 12,
            'name' => 'Org Unit T12',
        ]);
    }

    public function test_delete_org_unit_allows_matching_tenant(): void
    {
        $this->seedTenants();
        $this->seedOrgUnits();

        /** @var DeleteOrganizationUnitServiceInterface $service */
        $service = app(DeleteOrganizationUnitServiceInterface::class);

        $deleted = $service->execute([
            'id' => 8101,
            'tenant_id' => 11,
        ]);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('org_units', ['id' => 8101]);
    }

    private function seedTenants(): void
    {
        foreach ([11, 12] as $tenantId) {
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

    private function seedOrgUnits(): void
    {
        DB::table('org_units')->insert([
            [
                'id' => 8101,
                'tenant_id' => 11,
                'row_version' => 1,
                'type_id' => null,
                'parent_id' => null,
                'name' => 'Org Unit T11',
                'code' => 'OU11',
                'image_path' => null,
                'path' => 'ou11',
                'depth' => 0,
                'metadata' => null,
                'is_active' => true,
                'description' => null,
                '_lft' => 1,
                '_rgt' => 2,
                'default_revenue_account_id' => null,
                'default_expense_account_id' => null,
                'default_asset_account_id' => null,
                'default_liability_account_id' => null,
                'warehouse_id' => null,
                'manager_user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 8201,
                'tenant_id' => 12,
                'row_version' => 1,
                'type_id' => null,
                'parent_id' => null,
                'name' => 'Org Unit T12',
                'code' => 'OU12',
                'image_path' => null,
                'path' => 'ou12',
                'depth' => 0,
                'metadata' => null,
                'is_active' => true,
                'description' => null,
                '_lft' => 1,
                '_rgt' => 2,
                'default_revenue_account_id' => null,
                'default_expense_account_id' => null,
                'default_asset_account_id' => null,
                'default_liability_account_id' => null,
                'warehouse_id' => null,
                'manager_user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
