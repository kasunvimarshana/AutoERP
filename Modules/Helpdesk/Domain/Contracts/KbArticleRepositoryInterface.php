<?php

namespace Modules\Helpdesk\Domain\Contracts;

interface KbArticleRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByTenant(string $tenantId): iterable;
    public function findPublished(string $tenantId): iterable;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): void;
}
