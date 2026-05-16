<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Document\Domain\Repositories\DocumentTypeRepositoryInterface;
use Modules\Document\Infrastructure\Persistence\Eloquent\Models\DocumentTypeModel;

class EloquentDocumentTypeRepository implements DocumentTypeRepositoryInterface
{
    public function findCodeById(int $documentTypeId): ?string
    {
        return DocumentTypeModel::query()
            ->whereKey($documentTypeId)
            ->value('code');
    }

    public function findDefaultStatusById(int $documentTypeId): ?string
    {
        return DocumentTypeModel::query()
            ->whereKey($documentTypeId)
            ->value('default_status');
    }
}
