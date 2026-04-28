<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
use Tests\TestCase;

class OrganizationUnitUserDeleteTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_org_unit_user_rejects_cross_tenant_id(): void
    {
        $this->seedTenants();
        $this->seedUsers();
        $this->seedOrgUnits();
        $this->seedOrgUnitUsers();

        /** @var DeleteOrganizationUnitUserServiceInterface $service */
        $service = app(DeleteOrganizationUnitUserServiceInterface::class);

        $deleted = $service->execute([
            'id' => 9301,
            'tenant_id' => 11,
            'org_unit_id' => 9101,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('org_unit_users', [
            'id' => 9301,
            'tenant_id' => 12,
            'org_unit_id' => 9201,
        ]);
    }

    public function test_delete_org_unit_user_rejects_wrong_org_unit_context(): void
    {
        $this->seedTenants();
        $this->seedUsers();
        $this->seedOrgUnits();
        $this->seedOrgUnitUsers();

        /** @var DeleteOrganizationUnitUserServiceInterface $service */
        $service = app(DeleteOrganizationUnitUserServiceInterface::class);

        $deleted = $service->execute([
            'id' => 9101,
            'tenant_id' => 11,
            'org_unit_id' => 9201,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('org_unit_users', [
            'id' => 9101,
            'tenant_id' => 11,
            'org_unit_id' => 9101,
        ]);
    }

    public function test_delete_org_unit_user_allows_matching_scope(): void
    {
        $this->seedTenants();
        $this->seedUsers();
        $this->seedOrgUnits();
        $this->seedOrgUnitUsers();

        /** @var DeleteOrganizationUnitUserServiceInterface $service */
        $service = app(DeleteOrganizationUnitUserServiceInterface::class);

        $deleted = $service->execute([
            'id' => 9101,
            'tenant_id' => 11,
            'org_unit_id' => 9101,
        ]);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('org_unit_users', ['id' => 9101]);
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

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'first_name' => 'Tenant',
                'last_name' => 'Eleven',
                'email' => 'orgunit11.user@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('password-11'),
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 1201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'first_name' => 'Tenant',
                'last_name' => 'Twelve',
                'email' => 'orgunit12.user@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('password-12'),
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    private function seedOrgUnits(): void
    {
        DB::table('org_units')->insert([
            [
                'id' => 9101,
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
                'id' => 9201,
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

    private function seedOrgUnitUsers(): void
    {
        DB::table('org_unit_users')->insert([
            [
                'id' => 9101,
                'tenant_id' => 11,
                'org_unit_id' => 9101,
                'row_version' => 1,
                'user_id' => 1101,
                'role' => 'manager',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 9301,
                'tenant_id' => 12,
                'org_unit_id' => 9201,
                'row_version' => 1,
                'user_id' => 1201,
                'role' => 'manager',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
