<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

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

        return new DocumentDefinition(
            id: $model->id,
            tenantId: $model->tenant_id,
            documentTypeId: $model->document_type_id,
            version: $model->version,
            name: $model->name,
            headerSchema: $model->header_schema ?? [],
            allowedItemTypes: $model->allowed_item_types ?? [],
            validationRules: $model->validation_rules ?? [],
            formLayout: $model->form_layout ?? [],
            isActive: (bool) $model->is_active,
        );
    }
}
