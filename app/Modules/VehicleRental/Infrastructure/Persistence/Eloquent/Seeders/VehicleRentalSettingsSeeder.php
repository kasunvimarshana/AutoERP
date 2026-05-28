<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\Support\VehicleRentalDocumentSeedCatalog;

class VehicleRentalSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', VehicleRentalDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');
        $definitionIdsByTypeCode = [];

        foreach ($documentTypeIds as $code => $documentTypeId) {
            $definitionIdsByTypeCode[(string) $code] = (int) DB::table('document_definitions')
                ->where('tenant_id', $tenantId)
                ->where('document_type_id', (int) $documentTypeId)
                ->where('version', 1)
                ->value('id');
        }

        DB::table('vehicle_rental_settings')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
            ],
            [
                'rental_invoice_document_definition_id' => $definitionIdsByTypeCode['VEHICLE_RENTAL_INVOICE'] ?? null,
                'rental_agreement_document_definition_id' =>
                    $definitionIdsByTypeCode['VEHICLE_RENTAL_AGREEMENT'] ?? null,
                'running_chart_document_definition_id' =>
                    $definitionIdsByTypeCode['VEHICLE_RENTAL_RUNNING_CHART'] ?? null,
                'rental_replacement_document_definition_id' =>
                    $definitionIdsByTypeCode['VEHICLE_RENTAL_REPLACEMENT'] ?? null,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
