<?php

namespace App\Domain\Contracts;

interface CategoryRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?object;
    public function findBySlug(string $tenantId, string $slug): ?object;
    public function list(string $tenantId, array $params = []): mixed;
    public function getTree(string $tenantId): array;
    public function getChildren(string $tenantId, string $parentId): mixed;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function hasChildren(string $id): bool;
    public function hasProducts(string $id): bool;
}
