<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleServiceDocumentSeedCatalog;

class VehicleServiceDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VehicleServiceDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');

        foreach (VehicleServiceDocumentSeedCatalog::documentTypes() as $code => $data) {
            $values = [
                'name' => $data['name'],
                'description' => $data['description'],
                'module_scope' => 'vehicle_service',
                'default_status' => 'draft',
                'is_active' => true,
                'requires_source' => true,
                'supports_items' => true,
                'supports_attachments' => true,
                'supports_comments' => true,
                'supports_versions' => true,
                'supports_workflow' => true,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('document_types', 'tenant_id')) {
                $values['tenant_id'] = $tenantId > 0 ? $tenantId : null;
            }

            if (Schema::hasColumn('document_types', 'organization_unit_id')) {
                $values['organization_unit_id'] = null;
            }

            DB::table('document_types')->updateOrInsert(
                ['tenant_id' => $tenantId > 0 ? $tenantId : null, 'code' => $code],
                [...$values, 'created_at' => now()],
            );
        }
    }
}
