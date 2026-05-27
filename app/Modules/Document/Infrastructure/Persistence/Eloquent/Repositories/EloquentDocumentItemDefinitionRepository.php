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
                'dit.code as item_type_code',
                'did.field_schema',
                'did.validation_rules',
                'did.calculation_rule',
            ]);

        if ($definition === null) {
            return null;
        }

        return [
            'item_type_code' => (string) $definition->item_type_code,
            'field_schema' => json_decode((string) $definition->field_schema, true) ?: [],
            'validation_rules' => json_decode((string) ($definition->validation_rules ?? '[]'), true) ?: [],
            'calculation_rule' => $definition->calculation_rule !== null ? (string) $definition->calculation_rule : null,
        ];
    }
}
