<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\User\Constants\UserPermission;
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
        $permissionVersions = DB::table('permissions')
            ->where('tenant_id', $firstTenantId)
            ->orderBy('id')
            ->pluck('row_version', 'id')
            ->all();
        $roleVersion = (int) DB::table('roles')->where('id', $first['role_id'])->value('row_version');

        $executionContext->runForTenant(
            $firstTenantId,
            static fn (): array => $provisioner->provision($firstTenantId),
        );

        self::assertSame(
            $permissionVersions,
            DB::table('permissions')->where('tenant_id', $firstTenantId)->orderBy('id')->pluck('row_version', 'id')->all(),
        );
        self::assertSame(
            $roleVersion,
            (int) DB::table('roles')->where('id', $first['role_id'])->value('row_version'),
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
                ->where('name', \Modules\User\Constants\UserSystemRole::SUPER_ADMIN)
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
