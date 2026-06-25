<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\PlatformPermissionModel;
use Modules\User\Models\RoleModel;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserRoleModel;
use Modules\User\Models\UserOrganizationUnitModel;
use Modules\User\Services\Platform\PlatformPermissionCatalogSynchronizer;
use RuntimeException;

final class UserSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null || $organizationUnit === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $guardName = (string) config('auth.defaults.guard', 'web');
            $role = RoleModel::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->getKey(),
                    'name' => UserPermission::SUPER_ADMIN_ROLE,
                    'guard_name' => $guardName,
                ],
                [
                    'description' => 'Full tenant administration.',
                    'row_version' => 1,
                    'metadata' => json_encode([
                        'seed_source' => 'user_module',
                        'is_system' => true,
                        'is_protected' => true,
                    ], JSON_THROW_ON_ERROR),
                ],
            );

            $this->seedAccessPermissions($guardName);

            $email = $this->adminEmail();
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException('AUTOERP_ADMIN_EMAIL must be a valid email address.');
            }

            $user = UserModel::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'email' => $email],
                [
                    'username' => strtolower(trim((string) env('AUTOERP_ADMIN_USERNAME', 'admin'))),
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'email_verified_at' => now(),
                    'password' => app(PasswordHasherInterface::class)->hash($this->adminPassword()),
                    'status' => 'active',
                    'row_version' => 1,
                    'metadata' => ['seed_source' => 'user_module'],
                ],
            );

            $user->forceFill([
                'is_platform_operator' => false,
                'platform_login_email' => null,
            ])->save();

            if ($this->shouldSeedPlatformOperator()) {
                $operator = $this->seedPlatformOperator();
                $this->seedPlatformPermissions($operator);
            }

            if (Schema::hasTable('user_organization_units')) {
                UserOrganizationUnitModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'organization_unit_id' => $organizationUnit->getKey(),
                        'user_id' => $user->getKey(),
                    ],
                    [
                        'status' => 'active',
                        'is_default' => true,
                        'default_marker' => 'default',
                        'row_version' => 1,
                        'metadata' => json_encode(['seed_source' => 'user_module'], JSON_THROW_ON_ERROR),
                    ],
                );
            }

            if (Schema::hasTable('user_roles')) {
                UserRoleModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'user_id' => $user->getKey(),
                        'role_id' => $role->getKey(),
                    ],
                    [
                        'row_version' => 1,
                        'metadata' => json_encode(['seed_source' => 'user_module'], JSON_THROW_ON_ERROR),
                    ],
                );
            }
        }, 3);
    }

    private function seedAccessPermissions(string $guardName): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (UserPermission::descriptions() as $name => $description) {
                PermissionModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'name' => $name,
                        'guard_name' => $guardName,
                    ],
                    [
                        'module' => 'Users',
                        'description' => $description,
                        'row_version' => 1,
                        'metadata' => json_encode(['seed_source' => 'user_module'], JSON_THROW_ON_ERROR),
                    ],
                );
            }
        }
    }

    private function seedPlatformOperator(): UserModel
    {
        $email = $this->platformAdminEmail();
        return app(TenantExecutionContextInterface::class)->runAsControlPlane(function () use ($email): UserModel {
            $operator = UserModel::query()
                ->where('platform_login_email', $email)
                ->firstOrNew();

            $operator->forceFill([
                'tenant_id' => null,
                'platform_login_email' => $email,
                'email' => $email,
                'username' => null,
                'first_name' => 'Platform',
                'last_name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => app(PasswordHasherInterface::class)->hash($this->platformAdminPassword()),
                'status' => 'active',
                'is_platform_operator' => true,
                'row_version' => max(1, (int) ($operator->row_version ?? 0)),
                'metadata' => ['seed_source' => 'user_module', 'account_scope' => 'platform'],
            ])->save();

            return $operator;
        });
    }

    private function seedPlatformPermissions(UserModel $operator): void
    {
        if (! Schema::hasTable('platform_permissions') || ! Schema::hasTable('platform_operator_permissions')) {
            return;
        }

        app(PlatformPermissionCatalogSynchronizer::class)->synchronize();

        $permissionIds = PlatformPermissionModel::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
        PlatformOperatorPermissionModel::query()
            ->where('user_id', $operator->getKey())
            ->whereNotIn('platform_permission_id', $permissionIds)
            ->delete();
        foreach ($permissionIds as $permissionId) {
            PlatformOperatorPermissionModel::query()->firstOrCreate([
                'user_id' => $operator->getKey(),
                'platform_permission_id' => $permissionId,
            ]);
        }
    }

    private function platformAdminEmail(): string
    {
        $email = strtolower(trim((string) env('AUTOERP_PLATFORM_ADMIN_EMAIL', '')));
        if ($email === '' && app()->environment(['local', 'testing', 'development'])) {
            $email = $this->adminEmail();
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('AUTOERP_PLATFORM_ADMIN_EMAIL must be a valid email address.');
        }

        return $email;
    }

    private function platformAdminPassword(): string
    {
        $password = trim((string) env('AUTOERP_PLATFORM_ADMIN_PASSWORD', ''));
        if ($password !== '') {
            return $password;
        }
        if (app()->environment(['local', 'testing', 'development'])) {
            return $this->adminPassword();
        }

        throw new RuntimeException('AUTOERP_PLATFORM_ADMIN_PASSWORD is required when platform seeding is enabled.');
    }

    private function adminEmail(): string
    {
        $email = strtolower(trim((string) env('AUTOERP_ADMIN_EMAIL', '')));
        if ($email !== '') {
            return $email;
        }

        if (app()->environment(['local', 'testing', 'development'])) {
            return 'admin@example.com';
        }

        throw new RuntimeException('AUTOERP_ADMIN_EMAIL is required outside local/testing/development.');
    }


    private function shouldSeedPlatformOperator(): bool
    {
        return filter_var(
            env('AUTOERP_SEED_PLATFORM_OPERATOR', false),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    private function adminPassword(): string
    {
        $password = (string) env('AUTOERP_ADMIN_PASSWORD', '');
        if (trim($password) !== '') {
            return $password;
        }

        if (app()->environment(['local', 'testing', 'development'])) {
            return 'password';
        }

        throw new RuntimeException('AUTOERP_ADMIN_PASSWORD is required outside local/testing/development.');
    }
}
