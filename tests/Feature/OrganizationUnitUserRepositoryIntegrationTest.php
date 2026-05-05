<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;
use Tests\TestCase;

class OrganizationUnitUserRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_persists_and_maps_role_id(): void
    {
        $tenantId = 5101;
        $orgUnitId = 5102;
        $userId = 5103;
        $roleId = 5104;

        $this->insertTenant($tenantId);
        $this->insertOrgUnit($tenantId, $orgUnitId);
        $this->insertUser($tenantId, $orgUnitId, $userId);
        $this->insertRole($tenantId, $orgUnitId, $roleId);

        /** @var OrganizationUnitUserRepositoryInterface $repository */
        $repository = app(OrganizationUnitUserRepositoryInterface::class);

        $saved = $repository->save(new OrganizationUnitUser(
            tenantId: $tenantId,
            organizationUnitId: $orgUnitId,
            userId: $userId,
            roleId: $roleId,
            isPrimary: true,
        ));

        $this->assertNotNull($saved->getId());
        $this->assertSame($roleId, $saved->getRole());

        $row = DB::table('org_unit_users')->where('id', $saved->getId())->first();

        $this->assertNotNull($row);
        $this->assertSame($roleId, (int) $row->role_id);
        $this->assertSame(1, (int) $row->is_primary);

        $found = $repository->findByTenantOrgUnitAndUser($tenantId, $orgUnitId, $userId);
        $this->assertNotNull($found);
        $this->assertSame($roleId, $found->getRole());
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

    private function insertOrgUnit(int $tenantId, int $orgUnitId): void
    {
        DB::table('org_units')->insert([
            'id' => $orgUnitId,
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'type_id' => null,
            'parent_id' => null,
            'name' => 'Root Unit',
            'code' => 'ROOT-'.$orgUnitId,
            'image_path' => null,
            'path' => 'ROOT-'.$orgUnitId,
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
        ]);
    }

    private function insertUser(int $tenantId, int $orgUnitId, int $userId): void
    {
        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnitId,
            'row_version' => 1,
            'first_name' => 'Repo',
            'last_name' => 'User',
            'email' => 'repo.user'.$userId.'@example.com',
            'email_verified_at' => null,
            'password' => bcrypt('secret'),
            'phone' => null,
            'avatar' => null,
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertRole(int $tenantId, int $orgUnitId, int $roleId): void
    {
        DB::table('roles')->insert([
            'id' => $roleId,
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnitId,
            'row_version' => 1,
            'name' => 'Org Unit Role '.$roleId,
            'guard_name' => 'api',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
