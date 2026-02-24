<?php

namespace Modules\Workflow\Infrastructure\Repositories;

use Modules\Workflow\Domain\Contracts\WorkflowHistoryRepositoryInterface;
use Modules\Workflow\Infrastructure\Models\WorkflowHistoryModel;

class WorkflowHistoryRepository implements WorkflowHistoryRepositoryInterface
{
    public function findByDocument(string $documentType, string $documentId): iterable
    {
        return WorkflowHistoryModel::where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->orderBy('created_at')
            ->get();
    }

    public function create(array $data): object
    {
        return WorkflowHistoryModel::create($data);
    }
}
