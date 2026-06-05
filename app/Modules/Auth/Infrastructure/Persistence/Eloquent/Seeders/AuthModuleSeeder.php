<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tenantId = $this->seedLocalTenant();
            $organizationUnitId = $this->seedLocalOrganizationUnit($tenantId);

            $this->seedInternalProvider(null, null);
            $providerId = $this->seedInternalProvider($tenantId, $organizationUnitId);

            if ($this->shouldSeedLocalAdmin()) {
                $email = strtolower(trim((string) env('AUTH_LOCAL_ADMIN_EMAIL', 'admin@example.com')));
                $userId = $this->seedLocalAdminUser($tenantId, $organizationUnitId, $email);

                $this->seedLocalAdminIdentity($tenantId, $organizationUnitId, $providerId, $userId, $email);
            }
        });
    }

    private function shouldSeedLocalAdmin(): bool
    {
        return filter_var(env('SEED_AUTH_LOCAL_ADMIN', true), FILTER_VALIDATE_BOOLEAN);
    }

    private function seedLocalTenant(): int
    {
        $code = strtoupper((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP'));
        $existing = DB::table('tenants')->where('code', $code)->first();

        if ($existing !== null) {
            DB::table('tenants')->where('id', $existing->id)->update([
                'is_active' => true,
                'status' => 'active',
                'updated_at' => now(),
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('tenants')->insertGetId([
            'code' => $code,
            'configuration_scope' => 'tenant',
            'created_at' => now(),
            'cross_org_transactions' => false,
            'currency_id' => null,
            'is_active' => true,
            'is_isolated' => true,
            'isolation_key' => strtolower($code),
            'logo_path' => null,
            'metadata' => json_encode(['seed_source' => 'auth_module_local_tenant']),
            'name' => (string) env('AUTH_LOCAL_TENANT_NAME', 'AutoERP Local Tenant'),
            'row_version' => 1,
            'slug' => strtolower($code),
            'status' => 'active',
            'subscription_ends_at' => null,
            'tenant_plan_id' => null,
            'trial_ends_at' => null,
            'updated_at' => now(),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function seedLocalOrganizationUnit(int $tenantId): int
    {
        $name = (string) env('AUTH_LOCAL_ORGANIZATION_UNIT_NAME', 'Main Branch');
        $existing = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if ($existing !== null) {
            DB::table('organization_units')->where('id', $existing->id)->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('organization_units')->insertGetId([
            '_lft' => 0,
            '_rgt' => 0,
            'code' => (string) env('AUTH_LOCAL_ORGANIZATION_UNIT_CODE', 'MAIN'),
            'created_at' => now(),
            'depth' => 0,
            'description' => 'Local default organization unit for backend-connected auth.',
            'image_path' => null,
            'is_active' => true,
            'metadata' => json_encode(['seed_source' => 'auth_module_local_tenant']),
            'name' => $name,
            'parent_id' => null,
            'path' => '/main',
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'type_id' => null,
            'updated_at' => now(),
        ]);
    }

    private function seedInternalProvider(?int $tenantId, ?int $organizationUnitId): int
    {
        DB::table('auth_providers')->updateOrInsert(
            [
                'provider_key' => 'internal',
                'tenant_id' => $tenantId,
            ],
            [
                'config' => null,
                'driver' => 'internal',
                'guard_name' => 'web',
                'is_sso' => false,
                'metadata' => json_encode(['seed_source' => 'auth_module']),
                'name' => 'Internal Authentication',
                'organization_unit_id' => $organizationUnitId,
                'provider_name' => 'users',
                'row_version' => 1,
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $query = DB::table('auth_providers')
            ->where('provider_key', 'internal');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        return (int) $query->value('id');
    }

    private function seedLocalAdminUser(int $tenantId, int $organizationUnitId, string $email): int
    {
        $existing = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        $payload = [
            'avatar_path' => null,
            'date_of_birth' => null,
            'email_verified_at' => now(),
            'first_name' => (string) env('AUTH_LOCAL_ADMIN_FIRST_NAME', 'System'),
            'gender' => null,
            'last_name' => (string) env('AUTH_LOCAL_ADMIN_LAST_NAME', 'Administrator'),
            'marital_status' => null,
            'metadata' => json_encode([
                'identity_references' => ['internal' => $email],
                'roles' => ['admin'],
                'seed_source' => 'auth_module_local_admin',
            ]),
            'organization_unit_id' => $organizationUnitId,
            'password' => Hash::make((string) env('AUTH_LOCAL_ADMIN_PASSWORD', 'password')),
            'phone' => null,
            'preferences' => null,
            'remember_token' => null,
            'row_version' => 1,
            'status' => 'active',
            'tenant_id' => $tenantId,
            'updated_at' => now(),
        ];

        if ($existing !== null) {
            DB::table('users')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        return (int) DB::table('users')->insertGetId([
            ...$payload,
            'created_at' => now(),
            'email' => $email,
        ]);
    }

    private function seedLocalAdminIdentity(
        int $tenantId,
        int $organizationUnitId,
        int $providerId,
        int $userId,
        string $email,
    ): void {
        DB::table('auth_identities')->updateOrInsert(
            [
                'provider_id' => $providerId,
                'provider_user_key' => $email,
                'tenant_id' => $tenantId,
            ],
            [
                'claims' => json_encode(['email' => $email]),
                'is_primary' => true,
                'last_authenticated_at' => null,
                'metadata' => json_encode(['seed_source' => 'auth_module_local_admin']),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
                'user_id' => $userId,
                'verified_at' => now(),
            ],
        );
    }
}
