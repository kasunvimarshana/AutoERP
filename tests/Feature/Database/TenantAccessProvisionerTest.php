<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Database\Seeders\TenantPermissionSeeder;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class TenantAccessProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_provisioning_is_exact_tenant_scoped_and_idempotent(): void
    {
        $firstTenantId = $this->tenant('ACCESS-A');
        $secondTenantId = $this->tenant('ACCESS-B');
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();
        self::assertNotEmpty($definitions);

        $executionContext = app(TenantExecutionContextInterface::class);
        $provisioner = app(TenantAccessProvisionerInterface::class);

        $first = $executionContext->runForTenant(
            $firstTenantId,
            static fn (): array => $provisioner->provision($firstTenantId),
        );
        $executionContext->runForTenant(
            $firstTenantId,
            static fn (): array => $provisioner->provision($firstTenantId),
        );
        $second = $executionContext->runForTenant(
            $secondTenantId,
            static fn (): array => $provisioner->provision($secondTenantId),
        );

        $expectedPermissionCount = count($definitions);
        self::assertSame($expectedPermissionCount, $first['permission_count']);
        self::assertSame($expectedPermissionCount, $second['permission_count']);
        self::assertSame(
            $expectedPermissionCount,
            DB::table('permissions')->where('tenant_id', $firstTenantId)->count(),
        );
        self::assertSame(
            $expectedPermissionCount,
            DB::table('permissions')->where('tenant_id', $secondTenantId)->count(),
        );
        self::assertSame(
            $expectedPermissionCount,
            DB::table('role_permissions')
                ->where('tenant_id', $firstTenantId)
                ->where('role_id', $first['role_id'])
                ->count(),
        );
        self::assertSame(
            $expectedPermissionCount,
            DB::table('role_permissions')
                ->where('tenant_id', $secondTenantId)
                ->where('role_id', $second['role_id'])
                ->count(),
        );
        self::assertSame(
            1,
            DB::table('roles')
                ->where('tenant_id', $firstTenantId)
                ->where('name', UserSystemRole::SUPER_ADMIN_NAME)
                ->where('guard_name', UserGuard::TENANT_API)
                ->where('system_key', UserSystemRole::SUPER_ADMIN)
                ->where('is_system', true)
                ->count(),
        );
        self::assertTrue($executionContext->runForTenant(
            $firstTenantId,
            static fn (): bool => $provisioner->isReady(
                $firstTenantId,
                (int) $first['role_id'],
            ),
        ));
        self::assertTrue($executionContext->runForTenant(
            $secondTenantId,
            static fn (): bool => $provisioner->isReady(
                $secondTenantId,
                (int) $second['role_id'],
            ),
        ));
    }

    public function test_tenant_permission_seeder_runs_each_tenant_inside_a_tenant_context(): void
    {
        $tenantId = $this->tenant('ACCESS-SEED');
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        app(TenantPermissionSeeder::class)->run();

        $roleId = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('system_key', UserSystemRole::SUPER_ADMIN)
            ->value('id');

        self::assertIsNumeric($roleId);
        self::assertSame(
            count($definitions),
            DB::table('permissions')
                ->where('tenant_id', $tenantId)
                ->where('guard_name', UserGuard::TENANT_API)
                ->where('is_active', true)
                ->count(),
        );
        self::assertTrue(app(TenantExecutionContextInterface::class)->runForTenant(
            $tenantId,
            static fn (): bool => app(TenantAccessProvisionerInterface::class)->isReady($tenantId, (int) $roleId),
        ));
    }

    public function test_provisioning_handles_module_modifications_and_cleans_up_removed_permissions(): void
    {
        $tenantId = $this->tenant('ACCESS-SYNC');
        $executionContext = app(TenantExecutionContextInterface::class);
        $provisioner = app(TenantAccessProvisionerInterface::class);

        $provisioned = $executionContext->runForTenant(
            $tenantId,
            static fn (): array => $provisioner->provision($tenantId),
        );

        // Simulate a permission whose module/description was refactored in a module update
        $samplePermissionId = (int) DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->where('name', 'users.view')
            ->value('id');
        DB::table('permissions')
            ->where('id', $samplePermissionId)
            ->update(['module' => 'legacy_user', 'description' => 'Outdated description']);

        // Simulate a removed module/permission that was previously assigned to a custom role and a user
        $removedPermissionId = (int) DB::table('permissions')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'deprecated_module.items.view',
            'guard_name' => UserGuard::TENANT_API,
            'module' => 'deprecated_module',
            'description' => 'Removed module permission.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customRoleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Custom Role',
            'active_name_key' => 'custom role',
            'guard_name' => UserGuard::TENANT_API,
            'system_key' => null,
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_permissions')->insert([
            'tenant_id' => $tenantId,
            'role_id' => $customRoleId,
            'permission_id' => $removedPermissionId,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'sync-test@example.test',
        ]);
        DB::table('user_permissions')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'permission_id' => $removedPermissionId,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Re-provisioning must update the refactored module without throwing LogicException,
        // deactivate the removed permission, and clean up role_permissions & user_permissions.
        $executionContext->runForTenant(
            $tenantId,
            static fn (): array => $provisioner->provision($tenantId),
        );

        $this->assertDatabaseHas('permissions', [
            'id' => $samplePermissionId,
            'module' => 'user',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('permissions', [
            'id' => $removedPermissionId,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('role_permissions', [
            'tenant_id' => $tenantId,
            'role_id' => $customRoleId,
            'permission_id' => $removedPermissionId,
        ]);
        $this->assertDatabaseMissing('user_permissions', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'permission_id' => $removedPermissionId,
        ]);
        self::assertTrue($executionContext->runForTenant(
            $tenantId,
            static fn (): bool => $provisioner->isReady($tenantId, (int) $provisioned['role_id']),
        ));
    }

    public function test_tenant_permissions_sync_artisan_command_synchronizes_all_tenants(): void
    {
        $tenantId = $this->tenant('ACCESS-CMD');

        $this->artisan('tenant:permissions:sync', ['--tenant' => $tenantId])
            ->assertExitCode(0);

        $roleId = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->where('system_key', UserSystemRole::SUPER_ADMIN)
            ->value('id');
        self::assertIsNumeric($roleId);
        self::assertTrue(app(TenantExecutionContextInterface::class)->runForTenant(
            $tenantId,
            static fn (): bool => app(TenantAccessProvisionerInterface::class)->isReady($tenantId, (int) $roleId),
        ));
    }

    private function tenant(string $code): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => "Access {$code}",
            'slug' => strtolower($code),
            'status' => 'draft',
            'status_changed_at' => now(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
