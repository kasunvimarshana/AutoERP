<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Document\Domain\Entities\DocumentDefinition;
use Modules\Document\Domain\Repositories\DocumentDefinitionRepositoryInterface;
use Modules\Document\Infrastructure\Persistence\Eloquent\Models\DocumentDefinitionModel;

class EloquentDocumentDefinitionRepository implements DocumentDefinitionRepositoryInterface
{
    public function findActive(int $tenantId, int $documentTypeId): ?DocumentDefinition
    {
        $model = DocumentDefinitionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('document_type_id', $documentTypeId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if ($model === null) {
            return null;
        }

        $fields = DB::table('document_definition_fields')
            ->where('tenant_id', $tenantId)
            ->where('document_definition_id', $model->id)
            ->orderBy('display_order')
            ->get(['field_key', 'data_type', 'is_required']);

        $headerSchema = [];
        foreach ($fields as $field) {
            $headerSchema[(string) $field->field_key] = [
                'required' => (bool) $field->is_required,
                'type' => (string) $field->data_type,
            ];
        }

        $allowedItemTypes = DB::table('document_definition_item_types as ddit')
            ->join('document_item_types as dit', 'dit.id', '=', 'ddit.item_type_id')
            ->where('ddit.tenant_id', $tenantId)
            ->where('ddit.document_definition_id', $model->id)
            ->orderBy('ddit.display_order')
            ->pluck('dit.code')
            ->map(static fn ($code): string => (string) $code)
            ->all();

        $validationRules = DB::table('document_definition_settings')
            ->where('tenant_id', $tenantId)
            ->where('document_definition_id', $model->id)
            ->where('setting_group', 'validation_rules')
            ->get(['setting_key', 'value_string', 'value_text'])
            ->mapWithKeys(static fn ($row): array => [
                (string) $row->setting_key => (string) ($row->value_text ?? $row->value_string ?? ''),
            ])
            ->all();

        $sections = DB::table('document_definition_sections as s')
            ->where('s.tenant_id', $tenantId)
            ->where('s.document_definition_id', $model->id)
            ->orderBy('s.display_order')
            ->get(['s.id', 's.label']);

        $sectionIds = $sections->pluck('id')->all();
        $sectionFields = $sectionIds === []
            ? collect()
            : DB::table('document_definition_section_fields as sf')
                ->join('document_definition_fields as f', 'f.id', '=', 'sf.field_definition_id')
                ->where('sf.tenant_id', $tenantId)
                ->whereIn('sf.section_id', $sectionIds)
                ->orderBy('sf.display_order')
                ->get(['sf.section_id', 'f.field_key'])
                ->groupBy('section_id');

        $formLayout = [
            'sections' => $sections->map(function ($section) use ($sectionFields): array {
                return [
                    'title' => (string) $section->label,
                    'fields' => ($sectionFields->get($section->id) ?? collect())
                        ->pluck('field_key')
                        ->map(static fn ($field): string => (string) $field)
                        ->all(),
                ];
            })->all(),
        ];

        return new DocumentDefinition(
            id: $model->id,
            tenantId: $model->tenant_id,
            documentTypeId: $model->document_type_id,
            version: $model->version,
            name: $model->name,
            headerSchema: $headerSchema,
            allowedItemTypes: $allowedItemTypes,
            validationRules: $validationRules,
            formLayout: $formLayout,
            isActive: (bool) $model->is_active,
        );
    }
}
