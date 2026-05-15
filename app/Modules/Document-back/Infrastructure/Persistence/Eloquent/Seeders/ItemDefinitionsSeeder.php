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

        $records = [];

        foreach (DocumentSeedCatalog::itemDefinitions() as $itemCode => $definition) {
            $itemTypeId = $itemTypeIds[$itemCode] ?? null;

            if ($itemTypeId === null) {
                continue;
            }

            $records[] = [
                'tenant_id' => $tenantId,
                'item_type_id' => $itemTypeId,
                'version' => 1,
                'name' => $definition['name'],
                'field_schema' => json_encode($definition['field_schema'], JSON_THROW_ON_ERROR),
                'validation_rules' => json_encode($definition['validation_rules'], JSON_THROW_ON_ERROR),
                'calculation_rule' => $definition['calculation_rule'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('document_item_definitions')->upsert(
            $records,
            ['tenant_id', 'item_type_id', 'version'],
            ['name', 'field_schema', 'validation_rules', 'calculation_rule', 'is_active', 'updated_at']
        );
    }
}
