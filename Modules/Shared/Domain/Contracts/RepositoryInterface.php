<?php
namespace Modules\Shared\Domain\Contracts;
interface RepositoryInterface
{
    public function findById(string $id): ?object;
    public function findAll(array $filters = [], int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
}
