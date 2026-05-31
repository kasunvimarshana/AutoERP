<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\Support\DocumentSeedCatalog;

class DocumentDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) DB::table('tenants')->where('code', DocumentSeedCatalog::defaultTenantCode())->value('id');
        $documentTypeIds = DB::table('document_types')->pluck('id', 'code');
        $itemTypeIds = DB::table('document_item_types')->pluck('id', 'code');

        foreach (DocumentSeedCatalog::documentDefinitions() as $typeCode => $definition) {
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
                    ['label', 'data_type', 'is_required', 'display_order', 'validation_rule', 'updated_at'],
                );
            }

            $allowedItemTypes = is_array($definition['allowed_item_types'] ?? null)
                ? $definition['allowed_item_types']
                : [];
            $allowedRows = [];
            foreach ($allowedItemTypes as $order => $itemTypeCode) {
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

            $validationRules = is_array($definition['validation_rules'] ?? null) ? $definition['validation_rules'] : [];
            $settingRows = [];
            foreach ($validationRules as $ruleKey => $rule) {
                $settingRows[] = [
                    'tenant_id' => $tenantId,
                    'document_definition_id' => $definitionId,
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
                DB::table('document_definition_settings')->upsert(
                    $settingRows,
                    ['tenant_id', 'document_definition_id', 'setting_group', 'setting_key'],
                    ['value_type', 'value_text', 'updated_at'],
                );
            }

            $sections = $definition['form_layout']['sections'] ?? [];
            if (! is_array($sections)) {
                continue;
            }

            $definitionFields = DB::table('document_definition_fields')
                ->where('tenant_id', $tenantId)
                ->where('document_definition_id', $definitionId)
                ->get(['id', 'field_key'])
                ->keyBy('field_key');

            foreach ($sections as $sectionIndex => $section) {
                if (! is_array($section)) {
                    continue;
                }

                $sectionKey = (string) (
                    $section['section_key']
                    ?? ('section_' . ($sectionIndex + 1))
                );
                DB::table('document_definition_sections')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'document_definition_id' => $definitionId,
                        'section_key' => $sectionKey,
                    ],
                    [
                        'label' => (string) (
                            $section['title']
                            ?? $section['label']
                            ?? ('Section ' . ($sectionIndex + 1))
                        ),
                        'display_order' => (int) ($section['display_order'] ?? ($sectionIndex + 1)),
                        'is_visible' => (bool) ($section['is_visible'] ?? true),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );

                $sectionId = (int) DB::table('document_definition_sections')
                    ->where('tenant_id', $tenantId)
                    ->where('document_definition_id', $definitionId)
                    ->where('section_key', $sectionKey)
                    ->value('id');

                if ($sectionId <= 0) {
                    continue;
                }

                $fieldKeys = is_array($section['fields'] ?? null) ? $section['fields'] : [];
                foreach ($fieldKeys as $order => $fieldKey) {
                    $field = $definitionFields->get($fieldKey);
                    if ($field === null) {
                        continue;
                    }

                    DB::table('document_definition_section_fields')->updateOrInsert(
                        [
                            'tenant_id' => $tenantId,
                            'section_id' => $sectionId,
                            'field_definition_id' => (int) $field->id,
                        ],
                        [
                            'display_order' => $order + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }
    }
}
