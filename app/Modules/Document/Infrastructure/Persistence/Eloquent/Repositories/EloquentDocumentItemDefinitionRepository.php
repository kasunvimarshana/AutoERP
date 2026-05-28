<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Document\Domain\Repositories\DocumentItemDefinitionRepositoryInterface;

class EloquentDocumentItemDefinitionRepository implements DocumentItemDefinitionRepositoryInterface
{
    public function findActiveByItemType(int $tenantId, string $itemType): ?array
    {
        $definition = DB::table('document_item_definitions as did')
            ->join('document_item_types as dit', 'dit.id', '=', 'did.item_type_id')
            ->where('did.tenant_id', $tenantId)
            ->where('did.is_active', true)
            ->where(function ($query) use ($itemType): void {
                $query->where('dit.code', $itemType)
                    ->orWhere('dit.name', $itemType);
            })
            ->orderByDesc('did.version')
            ->first([
                'did.id as definition_id',
                'dit.code as item_type_code',
                'did.calculation_rule',
            ]);

        if ($definition === null) {
            return null;
        }

        $fieldSchema = DB::table('document_item_definition_fields')
            ->where('tenant_id', $tenantId)
            ->where('document_item_definition_id', (int) $definition->definition_id)
            ->orderBy('display_order')
            ->get(['field_key', 'data_type', 'is_required'])
            ->mapWithKeys(static fn ($field): array => [
                (string) $field->field_key => [
                    'required' => (bool) $field->is_required,
                    'type' => (string) $field->data_type,
                ],
            ])
            ->all();

        $validationRules = DB::table('document_item_definition_settings')
            ->where('tenant_id', $tenantId)
            ->where('document_item_definition_id', (int) $definition->definition_id)
            ->where('setting_group', 'validation_rules')
            ->get(['setting_key', 'value_string', 'value_text'])
            ->mapWithKeys(static fn ($row): array => [
                (string) $row->setting_key => (string) ($row->value_text ?? $row->value_string ?? ''),
            ])
            ->all();

        return [
            'item_type_code' => (string) $definition->item_type_code,
            'field_schema' => $fieldSchema,
            'validation_rules' => $validationRules,
            'calculation_rule' => $definition->calculation_rule !== null
                ? (string) $definition->calculation_rule
                : null,
        ];
    }
}
