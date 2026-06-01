<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleRentalDocumentSeedCatalog;

class VehicleRentalDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VehicleRentalDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');

        foreach (VehicleRentalDocumentSeedCatalog::documentTypes() as $documentType) {
            $values = [
                'name' => $documentType['name'],
                'description' => $documentType['description'] ?? $documentType['name'],
                'module_scope' => 'vehicle_rental',
                'default_status' => $documentType['default_status'],
                'is_active' => $documentType['is_active'],
                'requires_source' => $documentType['requires_source'],
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
                ['tenant_id' => $tenantId > 0 ? $tenantId : null, 'code' => $documentType['code']],
                [...$values, 'created_at' => now()],
            );
        }
    }
}
