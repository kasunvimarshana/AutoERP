<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenantCode = DocumentSeedCatalog::defaultTenantCode();
        $tenantName = DocumentSeedCatalog::defaultTenantName();

        $existingTenantId = DB::table('tenants')
            ->where(function ($query) use ($tenantCode): void {
                $query->where('code', $tenantCode)
                    ->orWhere('slug', strtolower($tenantCode));
            })
            ->value('id');

        if ($existingTenantId !== null) {
            DB::table('tenants')
                ->where('id', $existingTenantId)
                ->update([
                    'name' => $tenantName,
                    'slug' => strtolower($tenantCode),
                    'status' => 'active',
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('tenants')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'code' => $tenantCode,
                'name' => $tenantName,
                'slug' => strtolower($tenantCode),
                'status' => 'active',
                'is_active' => true,
                'configuration_scope' => 'tenant',
                'cross_org_transactions' => false,
                'is_isolated' => true,
                'isolation_key' => strtolower($tenantCode),
                'metadata' => json_encode(['seed_source' => 'document_module']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
