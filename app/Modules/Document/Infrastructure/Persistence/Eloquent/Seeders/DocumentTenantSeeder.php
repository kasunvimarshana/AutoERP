<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentTenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->upsert(
            [[
                'code' => DocumentSeedCatalog::DEFAULT_TENANT_CODE,
                'name' => 'Default Tenant',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['code'],
            ['name', 'is_active', 'updated_at']
        );
    }
}
