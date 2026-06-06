<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AuthSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tenantId = $this->defaultTenantId();
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

            $this->seedInternalProvider(null, null);
            $this->seedInternalProvider($tenantId, $organizationUnitId);
        });
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the Auth module seeder.');
        }

        return (int) $id;
    }

    private function defaultOrganizationUnitId(int $tenantId): ?int
    {
        return DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value('id');
    }

    private function seedInternalProvider(?int $tenantId, ?int $organizationUnitId): void
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
    }
}
