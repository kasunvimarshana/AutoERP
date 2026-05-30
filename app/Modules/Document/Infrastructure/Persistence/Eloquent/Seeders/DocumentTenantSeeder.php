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
        $existingTenantId = DB::table('tenants')
            ->where('code', DocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');

        if ($existingTenantId !== null) {
            DB::table('tenants')
                ->where('id', $existingTenantId)
                ->update([
                    'name' => 'Default Tenant',
                    'slug' => DocumentSeedCatalog::DEFAULT_TENANT_CODE,
                    'status' => 'active',
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('tenants')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'code' => DocumentSeedCatalog::DEFAULT_TENANT_CODE,
                'name' => 'Default Tenant',
                'slug' => DocumentSeedCatalog::DEFAULT_TENANT_CODE,
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
