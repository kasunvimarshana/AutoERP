<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

final class CoreSeeder extends Seeder
{
    private const DEFAULT_LOCALE = 'en';

    private const DEFAULT_TIMEZONE = 'UTC';

    private const DEFAULT_CURRENCY = 'USD';

    /**
     * @var list<string>
     */
    private const PERMISSION_MODULES = [
        'dashboard',
        'configuration',
        'tenant',
        'organization_unit',
        'auth',
        'user',
        'role',
        'permission',
        'audit',
        'sequence',
    ];

    /**
     * @var list<string>
     */
    private const PERMISSION_ACTIONS = [
        'view',
        'create',
        'update',
        'delete',
        'activate',
        'deactivate',
        'manage',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $currencyId = $this->seedReferenceConfiguration();
            [$tenantId, $organizationUnitId] = $this->seedTenantAndOrganization($currencyId);

            $roleIds = $this->seedRoles($tenantId);
            $permissionIds = $this->seedPermissions($tenantId);
            $this->grantAllPermissionsToRole($tenantId, $roleIds['super_admin'], $permissionIds);

            $adminUserId = $this->seedSuperAdminUser($tenantId, $organizationUnitId);
            if ($adminUserId !== null) {
                $this->seedUserTenantAccess($tenantId, $organizationUnitId, $adminUserId, $roleIds['super_admin']);
                $this->seedUserRole($tenantId, $organizationUnitId, $adminUserId, $roleIds['super_admin']);
                $this->grantAllPermissionsToUser($tenantId, $organizationUnitId, $adminUserId, $permissionIds);
            }

            $this->seedCoreConfigurations($tenantId);
        }, 3);
    }

    private function seedReferenceConfiguration(): ?int
    {
        if (Schema::hasTable('languages')) {
            DB::table('languages')->updateOrInsert(
                ['code' => $this->defaultLocale()],
                [
                    'name' => $this->defaultLocale() === 'en' ? 'English' : strtoupper($this->defaultLocale()),
                    'row_version' => 1,
                    'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (Schema::hasTable('timezones')) {
            DB::table('timezones')->updateOrInsert(
                ['name' => $this->defaultTimezone()],
                [
                    'offset' => $this->defaultTimezone() === 'UTC' ? '+00:00' : '+00:00',
                    'row_version' => 1,
                    'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        if (! Schema::hasTable('currencies')) {
            return null;
        }

        $currencyCode = $this->defaultCurrency();
        DB::table('currencies')->updateOrInsert(
            ['code' => $currencyCode],
            [
                'name' => $this->currencyName($currencyCode),
                'symbol' => $this->currencySymbol($currencyCode),
                'decimal_places' => 2,
                'is_active' => true,
                'row_version' => 1,
                'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->idBy('currencies', ['code' => $currencyCode]);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function seedTenantAndOrganization(?int $currencyId): array
    {
        $tenantId = $this->seedDefaultTenant($currencyId);
        $organizationTypeId = $this->seedDefaultOrganizationUnitType($tenantId);
        $organizationUnitId = $this->seedDefaultOrganizationUnit($tenantId, $organizationTypeId);

        return [$tenantId, $organizationUnitId];
    }

    private function seedDefaultTenant(?int $currencyId): int
    {
        $tenantCode = $this->defaultTenantCode();
        $existing = DB::table('tenants')->where('code', $tenantCode)->first();
        $metadata = [
            'seed_source' => 'core_bootstrap',
            'default_locale' => $this->defaultLocale(),
            'default_timezone' => $this->defaultTimezone(),
            'default_currency' => $this->defaultCurrency(),
        ];

        $values = [
            'configuration_scope' => 'tenant',
            'cross_org_transactions' => false,
            'currency_id' => $currencyId,
            'is_active' => true,
            'is_isolated' => true,
            'isolation_key' => strtolower($tenantCode),
            'logo_path' => null,
            'metadata' => $this->json($metadata),
            'name' => $this->defaultTenantName(),
            'row_version' => 1,
            'slug' => Str::slug($tenantCode),
            'status' => 'active',
            'subscription_ends_at' => null,
            'tenant_plan_id' => null,
            'trial_ends_at' => null,
            'updated_at' => now(),
        ];

        if ($existing !== null) {
            DB::table('tenants')->where('id', $existing->id)->update($values);

            return (int) $existing->id;
        }

        DB::table('tenants')->insert($values + [
            'code' => $tenantCode,
            'created_at' => now(),
            'uuid' => (string) Str::uuid(),
        ]);

        return $this->requiredIdBy('tenants', ['code' => $tenantCode]);
    }

    private function seedDefaultOrganizationUnitType(int $tenantId): ?int
    {
        if (! Schema::hasTable('organization_unit_types')) {
            return null;
        }

        DB::table('organization_unit_types')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'name' => 'Branch',
            ],
            [
                'level' => 0,
                'is_active' => true,
                'row_version' => 1,
                'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->idBy('organization_unit_types', ['tenant_id' => $tenantId, 'name' => 'Branch']);
    }

    private function seedDefaultOrganizationUnit(int $tenantId, ?int $typeId): int
    {
        $code = $this->defaultOrganizationUnitCode();
        $name = $this->defaultOrganizationUnitName();

        DB::table('organization_units')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'name' => $name,
            ],
            [
                '_lft' => 0,
                '_rgt' => 0,
                'code' => $code,
                'depth' => 0,
                'description' => 'Default branch for bootstrap and local testing.',
                'image_path' => null,
                'is_active' => true,
                'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                'parent_id' => null,
                'path' => '/'.strtolower($code),
                'row_version' => 1,
                'type_id' => $typeId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->requiredIdBy('organization_units', ['tenant_id' => $tenantId, 'name' => $name]);
    }

    /**
     * @return array{super_admin:int,admin:int,user:int}
     */
    private function seedRoles(int $tenantId): array
    {
        $guardName = $this->guardName();
        $roles = [
            'super_admin' => ['name' => 'Super Admin', 'description' => 'Full platform administration for this tenant.'],
            'admin' => ['name' => 'Admin', 'description' => 'Tenant administrator.'],
            'user' => ['name' => 'User', 'description' => 'Standard authenticated user.'],
        ];

        $ids = [];
        foreach ($roles as $key => $role) {
            DB::table('roles')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'name' => $role['name'],
                    'guard_name' => $guardName,
                ],
                [
                    'description' => $role['description'],
                    'organization_unit_id' => null,
                    'row_version' => 1,
                    'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $ids[$key] = $this->requiredIdBy('roles', [
                'tenant_id' => $tenantId,
                'name' => $role['name'],
                'guard_name' => $guardName,
            ]);
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function seedPermissions(int $tenantId): array
    {
        $guardName = $this->guardName();
        $permissionIds = [];

        foreach (self::PERMISSION_MODULES as $module) {
            foreach (self::PERMISSION_ACTIONS as $action) {
                $name = $module.'.'.$action;
                DB::table('permissions')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'name' => $name,
                        'guard_name' => $guardName,
                    ],
                    [
                        'description' => 'Allows '.str_replace('_', ' ', $action).' access for '.str_replace('_', ' ', $module).'.',
                        'module' => $module,
                        'organization_unit_id' => null,
                        'row_version' => 1,
                        'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );

                $permissionIds[] = $this->requiredIdBy('permissions', [
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'guard_name' => $guardName,
                ]);
            }
        }

        return array_values(array_unique($permissionIds));
    }

    /**
     * @param  list<int>  $permissionIds
     */
    private function grantAllPermissionsToRole(int $tenantId, int $roleId, array $permissionIds): void
    {
        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'organization_unit_id' => null,
                    'row_version' => 1,
                    'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedSuperAdminUser(int $tenantId, int $organizationUnitId): ?int
    {
        if (! $this->shouldSeedSuperAdmin()) {
            return null;
        }

        $email = $this->superAdminEmail();
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('AUTH_LOCAL_ADMIN_EMAIL must contain a valid email address.');
        }

        $password = $this->superAdminPassword();

        DB::table('users')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'email' => $email,
            ],
            [
                'date_of_birth' => null,
                'email_verified_at' => now(),
                'first_name' => (string) env('AUTH_LOCAL_ADMIN_FIRST_NAME', 'System'),
                'gender' => null,
                'last_name' => (string) env('AUTH_LOCAL_ADMIN_LAST_NAME', 'Administrator'),
                'marital_status' => null,
                'metadata' => $this->json([
                    'seed_source' => 'core_bootstrap',
                    'role' => 'Super Admin',
                ]),
                'organization_unit_id' => $organizationUnitId,
                'password' => Hash::make($password),
                'phone' => null,
                'preferences' => null,
                'remember_token' => null,
                'row_version' => 1,
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->requiredIdBy('users', ['tenant_id' => $tenantId, 'email' => $email]);
    }

    private function seedUserTenantAccess(int $tenantId, int $organizationUnitId, int $userId, int $roleId): void
    {
        DB::table('user_tenants')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'user_id' => $userId,
            ],
            [
                'role_id' => $roleId,
                'is_default' => true,
                'row_version' => 1,
                'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function seedUserRole(int $tenantId, int $organizationUnitId, int $userId, int $roleId): void
    {
        DB::table('user_roles')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'role_id' => $roleId,
            ],
            [
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    /**
     * @param  list<int>  $permissionIds
     */
    private function grantAllPermissionsToUser(int $tenantId, int $organizationUnitId, int $userId, array $permissionIds): void
    {
        foreach ($permissionIds as $permissionId) {
            DB::table('user_permissions')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'row_version' => 1,
                    'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedCoreConfigurations(int $tenantId): void
    {
        if (Schema::hasTable('system_configurations')) {
            foreach ($this->configurationDefaults() as $key => $value) {
                DB::table('system_configurations')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => (string) $value,
                        'value_type' => 'string',
                        'source' => 'database',
                        'description' => 'Bootstrap default for '.$key.'.',
                        'row_version' => 1,
                        'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }

        if (! Schema::hasTable('tenant_configurations')) {
            return;
        }

        foreach ($this->configurationDefaults() as $key => $value) {
            DB::table('tenant_configurations')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                ],
                [
                    'value' => (string) $value,
                    'value_type' => 'string',
                    'source' => 'database',
                    'description' => 'Tenant bootstrap default for '.$key.'.',
                    'row_version' => 1,
                    'metadata' => $this->json(['seed_source' => 'core_bootstrap']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    /**
     * @return array<string,string>
     */
    private function configurationDefaults(): array
    {
        return [
            'app.locale' => $this->defaultLocale(),
            'app.timezone' => $this->defaultTimezone(),
            'app.currency' => $this->defaultCurrency(),
        ];
    }

    private function shouldSeedSuperAdmin(): bool
    {
        if ((bool) env('SEED_AUTH_LOCAL_ADMIN', false)) {
            return true;
        }

        return app()->environment(['local', 'testing']);
    }

    private function superAdminPassword(): string
    {
        $password = (string) env('AUTH_LOCAL_ADMIN_PASSWORD', '');
        if ($password !== '') {
            return $password;
        }

        if (app()->environment(['local', 'testing'])) {
            return 'password';
        }

        throw new RuntimeException('AUTH_LOCAL_ADMIN_PASSWORD is required when seeding a super admin outside local/testing.');
    }

    private function superAdminEmail(): string
    {
        $email = strtolower(trim((string) env('AUTH_LOCAL_ADMIN_EMAIL', '')));
        if ($email !== '') {
            return $email;
        }

        if (app()->environment(['local', 'testing'])) {
            return 'admin@example.com';
        }

        throw new RuntimeException('AUTH_LOCAL_ADMIN_EMAIL is required when seeding a super admin outside local/testing.');
    }

    private function defaultTenantCode(): string
    {
        return strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
    }

    private function defaultTenantName(): string
    {
        return trim((string) env('AUTH_LOCAL_TENANT_NAME', 'AutoERP Local Tenant'));
    }

    private function defaultOrganizationUnitCode(): string
    {
        return strtoupper(trim((string) env('AUTH_LOCAL_ORGANIZATION_UNIT_CODE', 'MAIN')));
    }

    private function defaultOrganizationUnitName(): string
    {
        return trim((string) env('AUTH_LOCAL_ORGANIZATION_UNIT_NAME', 'Main Branch'));
    }

    private function defaultLocale(): string
    {
        $locale = trim((string) env('AUTH_LOCAL_TENANT_LOCALE', config('app.locale', self::DEFAULT_LOCALE)));

        return $locale !== '' ? $locale : self::DEFAULT_LOCALE;
    }

    private function defaultTimezone(): string
    {
        $timezone = trim((string) env('AUTH_LOCAL_TENANT_TIMEZONE', config('app.timezone', self::DEFAULT_TIMEZONE)));

        return $timezone !== '' ? $timezone : self::DEFAULT_TIMEZONE;
    }

    private function defaultCurrency(): string
    {
        $currency = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CURRENCY', self::DEFAULT_CURRENCY)));

        return $currency !== '' ? $currency : self::DEFAULT_CURRENCY;
    }

    private function guardName(): string
    {
        $guardName = trim((string) config('auth.defaults.guard', 'web'));

        return $guardName !== '' ? $guardName : 'web';
    }

    private function currencyName(string $code): string
    {
        return match ($code) {
            'LKR' => 'Sri Lankan Rupee',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'Pound Sterling',
            default => $code,
        };
    }

    private function currencySymbol(string $code): string
    {
        return match ($code) {
            'LKR' => 'Rs',
            'USD' => '$',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            default => $code,
        };
    }

    /**
     * @param  array<string,mixed>  $criteria
     */
    private function idBy(string $table, array $criteria): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);
        foreach ($criteria as $column => $value) {
            $query = $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        $id = $query->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string,mixed>  $criteria
     */
    private function requiredIdBy(string $table, array $criteria): int
    {
        $id = $this->idBy($table, $criteria);
        if ($id === null) {
            throw new RuntimeException('Failed to resolve seeded record id for table ['.$table.'].');
        }

        return $id;
    }

    /**
     * @param  array<string,mixed>  $value
     */
    private function json(array $value): string
    {
        return (string) json_encode($value, JSON_THROW_ON_ERROR);
    }
}
