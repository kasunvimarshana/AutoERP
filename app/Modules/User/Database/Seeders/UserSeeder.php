<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\RoleModel;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserRoleModel;
use Modules\User\Models\UserTenantModel;
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
                    'organization_unit_id' => null,
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
                    'organization_unit_id' => $organizationUnit->getKey(),
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

            if (Schema::hasTable('user_tenants')) {
                UserTenantModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'organization_unit_id' => $organizationUnit->getKey(),
                        'user_id' => $user->getKey(),
                    ],
                    [
                        'role_id' => $role->getKey(),
                        'is_default' => true,
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
                        'organization_unit_id' => $organizationUnit->getKey(),
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
                        'organization_unit_id' => null,
                        'module' => 'Users',
                        'description' => $description,
                        'row_version' => 1,
                        'metadata' => json_encode(['seed_source' => 'user_module'], JSON_THROW_ON_ERROR),
                    ],
                );
            }
        }
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
