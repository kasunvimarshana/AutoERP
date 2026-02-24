<?php

namespace Modules\Workflow\Domain\Contracts;

interface WorkflowRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByTenant(string $tenantId): iterable;
    public function findActiveByDocumentType(string $tenantId, string $documentType): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): void;
}
