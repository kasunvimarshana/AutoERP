<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\Support\PurchaseDocumentSeedCatalog;

class PurchaseDocumentDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')
            ->where('code', PurchaseDocumentSeedCatalog::DEFAULT_TENANT_CODE)
            ->value('id');
        if ($tenantId <= 0) {
            return;
        }

        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');
        $itemTypeIds = DB::table('document_item_types')->pluck('id', 'code');

        foreach (PurchaseDocumentSeedCatalog::documentDefinitions() as $typeCode => $definition) {
            $documentTypeId = $documentTypeIds[$typeCode] ?? null;
            if ($documentTypeId === null) {
                continue;
            }

            DB::table('document_definitions')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'document_type_id' => $documentTypeId,
                    'version' => 1,
                ],
                [
                    'name' => $definition['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $definitionId = (int) DB::table('document_definitions')
                ->where('tenant_id', $tenantId)
                ->where('document_type_id', $documentTypeId)
                ->where('version', 1)
                ->value('id');

            if ($definitionId <= 0) {
                continue;
            }

            $headerSchema = is_array($definition['header_schema'] ?? null) ? $definition['header_schema'] : [];
            $fieldRows = [];
            $fieldOrder = 1;

            foreach ($headerSchema as $fieldKey => $rules) {
                $fieldRows[] = [
                    'tenant_id' => $tenantId,
                    'document_definition_id' => $definitionId,
                    'field_key' => (string) $fieldKey,
                    'label' => (string) $fieldKey,
                    'data_type' => (string) ($rules['type'] ?? 'text'),
                    'is_required' => (bool) ($rules['required'] ?? false),
                    'display_order' => $fieldOrder++,
                    'default_value' => null,
                    'validation_rule' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($fieldRows !== []) {
                DB::table('document_definition_fields')->upsert(
                    $fieldRows,
                    ['tenant_id', 'document_definition_id', 'field_key'],
                    ['label', 'data_type', 'is_required', 'display_order', 'updated_at'],
                );
            }

            $allowedRows = [];
            foreach ((array) ($definition['allowed_item_types'] ?? []) as $order => $itemTypeCode) {
                $itemTypeId = $itemTypeIds[$itemTypeCode] ?? null;
                if ($itemTypeId === null) {
                    continue;
                }

                $allowedRows[] = [
                    'tenant_id' => $tenantId,
                    'document_definition_id' => $definitionId,
                    'item_type_id' => (int) $itemTypeId,
                    'display_order' => $order + 1,
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($allowedRows !== []) {
                DB::table('document_definition_item_types')->upsert(
                    $allowedRows,
                    ['tenant_id', 'document_definition_id', 'item_type_id'],
                    ['display_order', 'is_required', 'updated_at'],
                );
            }
        }
    }
}
