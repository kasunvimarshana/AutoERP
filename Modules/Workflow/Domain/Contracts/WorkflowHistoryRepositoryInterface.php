<?php

namespace Modules\Workflow\Domain\Contracts;

interface WorkflowHistoryRepositoryInterface
{
    public function findByDocument(string $documentType, string $documentId): iterable;
    public function create(array $data): object;
}
