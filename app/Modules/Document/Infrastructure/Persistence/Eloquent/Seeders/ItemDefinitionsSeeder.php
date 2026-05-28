<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class ItemDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')->where('code', DocumentSeedCatalog::DEFAULT_TENANT_CODE)->value('id');
        $itemTypeIds = DB::table('document_item_types')->pluck('id', 'code');

        foreach (DocumentSeedCatalog::itemDefinitions() as $itemCode => $definition) {
            $itemTypeId = $itemTypeIds[$itemCode] ?? null;

            if ($itemTypeId === null) {
                continue;
            }

            DB::table('document_item_definitions')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'item_type_id' => $itemTypeId,
                    'version' => 1,
                ],
                [
                    'name' => $definition['name'],
                    'calculation_rule' => $definition['calculation_rule'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $definitionId = (int) DB::table('document_item_definitions')
                ->where('tenant_id', $tenantId)
                ->where('item_type_id', $itemTypeId)
                ->where('version', 1)
                ->value('id');

            if ($definitionId <= 0) {
                continue;
            }

            $fieldSchema = is_array($definition['field_schema'] ?? null) ? $definition['field_schema'] : [];
            $fieldRows = [];
            $fieldOrder = 1;
            foreach ($fieldSchema as $fieldKey => $rules) {
                $fieldRows[] = [
                    'tenant_id' => $tenantId,
                    'document_item_definition_id' => $definitionId,
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
                DB::table('document_item_definition_fields')->upsert(
                    $fieldRows,
                    ['tenant_id', 'document_item_definition_id', 'field_key'],
                    ['label', 'data_type', 'is_required', 'display_order', 'validation_rule', 'updated_at']
                );
            }

            $validationRules = is_array($definition['validation_rules'] ?? null) ? $definition['validation_rules'] : [];
            $settingRows = [];
            foreach ($validationRules as $ruleKey => $rule) {
                $settingRows[] = [
                    'tenant_id' => $tenantId,
                    'document_item_definition_id' => $definitionId,
                    'setting_group' => 'validation_rules',
                    'setting_key' => (string) $ruleKey,
                    'value_type' => 'text',
                    'value_string' => null,
                    'value_integer' => null,
                    'value_decimal' => null,
                    'value_boolean' => null,
                    'value_date' => null,
                    'value_datetime' => null,
                    'value_text' => (string) $rule,
                    'value_file_id' => null,
                    'value_reference_type' => null,
                    'value_reference_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($settingRows !== []) {
                DB::table('document_item_definition_settings')->upsert(
                    $settingRows,
                    ['tenant_id', 'document_item_definition_id', 'setting_group', 'setting_key'],
                    ['value_type', 'value_text', 'updated_at']
                );
            }
        }
    }
}
