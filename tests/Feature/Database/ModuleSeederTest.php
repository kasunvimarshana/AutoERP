<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\User\Constants\UserPermission;
use Modules\User\Database\Seeders\SuperAdminPermissionSeeder;
use Tests\TestCase;

final class ModuleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_permissions_are_assigned_for_every_tenant_and_reruns_are_idempotent(): void
    {
        $firstTenantId = $this->tenant('SEED-A');
        $secondTenantId = $this->tenant('SEED-B');
        $firstRoleId = $this->role($firstTenantId);
        $secondRoleId = $this->role($secondTenantId);

        $firstPermissionIds = [
            $this->permission($firstTenantId, 'seed-a.view'),
            $this->permission($firstTenantId, 'seed-a.manage'),
        ];
        $secondPermissionId = $this->permission($secondTenantId, 'seed-b.view');
        $this->permission($secondTenantId, 'seed-b.web-only', 'web');

        $seeder = new SuperAdminPermissionSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(
            $firstPermissionIds,
            DB::table('role_permissions')
                ->where('tenant_id', $firstTenantId)
                ->where('role_id', $firstRoleId)
                ->orderBy('permission_id')
                ->pluck('permission_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
        );
        $this->assertSame(
            [$secondPermissionId],
            DB::table('role_permissions')
                ->where('tenant_id', $secondTenantId)
                ->where('role_id', $secondRoleId)
                ->pluck('permission_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
        );
        $this->assertSame(3, DB::table('role_permissions')->count());
    }

    private function tenant(string $code): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => "Seeder {$code}",
            'slug' => strtolower($code),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function role(int $tenantId): int
    {
        return (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserPermission::SUPER_ADMIN_ROLE,
            'guard_name' => 'api',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function permission(int $tenantId, string $name, string $guard = 'api'): int
    {
        return (int) DB::table('permissions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'guard_name' => $guard,
            'module' => 'Testing',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
