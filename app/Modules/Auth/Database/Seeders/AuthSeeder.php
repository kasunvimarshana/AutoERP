<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Models\AuthProviderModel;
use Modules\User\Constants\UserGuard;
use Database\Seeders\Concerns\ResolvesSeedContext;

final class AuthSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('auth_providers')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $this->seedInternalProvider((int) $tenant->getKey(), $organizationUnit?->getKey());
        }, 3);
    }

    private function seedInternalProvider(int $tenantId, ?int $organizationUnitId): void
    {
        AuthProviderModel::query()->updateOrCreate(
            [
                'provider_key' => 'internal',
                'tenant_id' => $tenantId,
            ],
            [
                'config' => null,
                'driver' => 'internal',
                'guard_name' => UserGuard::TENANT_API,
                'is_sso' => false,
                'metadata' => json_encode(['seed_source' => 'auth_module'], JSON_THROW_ON_ERROR),
                'name' => 'Internal Authentication',
                'organization_unit_id' => $organizationUnitId,
                'provider_name' => 'users',
                'row_version' => 1,
                'status' => 'active',
            ],
        );
    }
}
