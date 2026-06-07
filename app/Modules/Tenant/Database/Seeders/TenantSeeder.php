<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Tenant\Models\TenantModel;
use Ramsey\Uuid\Uuid;

final class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        DB::transaction(function (): void {
            $code = strtoupper(trim((string) env('AUTOERP_TENANT_CODE', 'AUTOERP')));
            $name = trim((string) env('AUTOERP_TENANT_NAME', 'AutoERP'));

            TenantModel::query()->updateOrCreate(
                ['code' => $code],
                [
                    'uuid' => Uuid::uuid5(Uuid::NAMESPACE_DNS, 'autoerp.local/tenant/'.$code)->toString(),
                    'name' => $name,
                    'slug' => Str::slug($code),
                    'cross_org_transactions' => false,
                    'status' => 'active',
                    'is_active' => true,
                    'is_isolated' => true,
                    'isolation_key' => strtolower($code),
                    'configuration_scope' => 'tenant',
                    'row_version' => 1,
                    'metadata' => ['seed_source' => 'tenant_module'],
                ],
            );
        }, 3);
    }
}
