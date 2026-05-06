<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Tests\TestCase;

class UserRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    // ── User ──────────────────────────────────────────────────────────────────

    public function test_user_create_and_find_by_tenant_and_id(): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $userId = $repository->createRecord([
            'tenant_id' => 11,
            'org_unit_id' => null,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $found = $repository->findByTenantAndId(11, $userId);

        $this->assertNotNull($found);
        $this->assertSame($userId, $found->getId());
        $this->assertSame(11, $found->getTenantId());
        $this->assertSame('alice@example.com', $found->getEmail()->value());
    }

    public function test_user_find_by_tenant_and_id_is_tenant_scoped(): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $userId = $repository->createRecord([
            'tenant_id' => 11,
            'org_unit_id' => null,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $foundCorrectTenant = $repository->findByTenantAndId(11, $userId);
        $foundWrongTenant = $repository->findByTenantAndId(12, $userId);

        $this->assertNotNull($foundCorrectTenant);
        $this->assertNull($foundWrongTenant);
    }

    public function test_user_find_by_email_returns_correct_user(): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $userId = $repository->createRecord([
            'tenant_id' => 11,
            'org_unit_id' => null,
            'first_name' => 'Carol',
            'last_name' => 'White',
            'email' => 'carol@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $found = $repository->findByEmail(11, 'carol@example.com');
        $notFound = $repository->findByEmail(12, 'carol@example.com');

        $this->assertNotNull($found);
        $this->assertSame($userId, $found->getId());
        $this->assertNull($notFound);
    }

    // ── Role ──────────────────────────────────────────────────────────────────

    public function test_role_save_and_find_by_name(): void
    {
        /** @var RoleRepositoryInterface $repository */
        $repository = app(RoleRepositoryInterface::class);

        $saved = $repository->save(new Role(
            tenantId: 11,
            name: 'Manager',
            guardName: 'api',
            description: 'Can manage resources',
        ));

        $found = $repository->findByName(11, 'Manager');
        $notFound = $repository->findByName(11, 'NonExistent');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Manager', $found->getName());
        $this->assertNull($notFound);
    }

    public function test_role_find_by_name_is_tenant_scoped(): void
    {
        /** @var RoleRepositoryInterface $repository */
        $repository = app(RoleRepositoryInterface::class);

        $roleT11 = $repository->save(new Role(tenantId: 11, name: 'Admin', guardName: 'api'));
        $roleT12 = $repository->save(new Role(tenantId: 12, name: 'Admin', guardName: 'api'));

        $foundT11 = $repository->findByName(11, 'Admin');
        $foundT12 = $repository->findByName(12, 'Admin');

        $this->assertNotNull($foundT11);
        $this->assertNotNull($foundT12);
        $this->assertSame($roleT11->getId(), $foundT11->getId());
        $this->assertSame($roleT12->getId(), $foundT12->getId());
        $this->assertNotSame($foundT11->getId(), $foundT12->getId());
    }

    public function test_role_sync_permissions(): void
    {
        /** @var RoleRepositoryInterface $roleRepository */
        $roleRepository = app(RoleRepositoryInterface::class);

        /** @var PermissionRepositoryInterface $permRepository */
        $permRepository = app(PermissionRepositoryInterface::class);

        $perm1 = $permRepository->save(new Permission(tenantId: 11, name: 'user.create', guardName: 'api', module: 'User'));
        $perm2 = $permRepository->save(new Permission(tenantId: 11, name: 'user.delete', guardName: 'api', module: 'User'));

        $role = $roleRepository->save(new Role(tenantId: 11, name: 'SuperAdmin', guardName: 'api'));

        $roleRepository->syncPermissions($role, [$perm1->getId(), $perm2->getId()]);

        $count = DB::table('permission_role')->where('role_id', $role->getId())->count();
        $this->assertSame(2, $count);

        // Sync with only one permission — verify the other is removed
        $roleRepository->syncPermissions($role, [$perm1->getId()]);

        $countAfter = DB::table('permission_role')->where('role_id', $role->getId())->count();
        $this->assertSame(1, $countAfter);

        $remaining = DB::table('permission_role')->where('role_id', $role->getId())->first();
        $this->assertSame($perm1->getId(), (int) $remaining->permission_id);
    }

    // ── Permission ────────────────────────────────────────────────────────────

    public function test_permission_save_and_find_by_name(): void
    {
        /** @var PermissionRepositoryInterface $repository */
        $repository = app(PermissionRepositoryInterface::class);

        $saved = $repository->save(new Permission(
            tenantId: 11,
            name: 'product.view',
            guardName: 'api',
            module: 'Product',
            description: 'View products',
        ));

        $found = $repository->findByName(11, 'product.view');
        $notFound = $repository->findByName(12, 'product.view');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('product.view', $found->getName());
        $this->assertSame('Product', $found->getModule());
        $this->assertNull($notFound);
    }

    public function test_permission_find_by_name_is_tenant_scoped(): void
    {
        /** @var PermissionRepositoryInterface $repository */
        $repository = app(PermissionRepositoryInterface::class);

        $permT11 = $repository->save(new Permission(tenantId: 11, name: 'order.view', guardName: 'api', module: 'Sales'));
        $permT12 = $repository->save(new Permission(tenantId: 12, name: 'order.view', guardName: 'api', module: 'Sales'));

        $foundT11 = $repository->findByName(11, 'order.view');
        $foundT12 = $repository->findByName(12, 'order.view');

        $this->assertNotNull($foundT11);
        $this->assertNotNull($foundT12);
        $this->assertNotSame($permT11->getId(), $permT12->getId());
        $this->assertSame(11, $foundT11->getTenantId());
        $this->assertSame(12, $foundT12->getTenantId());
    }


    // ── Seed ──────────────────────────────────────────────────────────────────

    private function seedReferenceData(): void
    {
        $this->insertTenant(11);
        $this->insertTenant(12);
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
}
