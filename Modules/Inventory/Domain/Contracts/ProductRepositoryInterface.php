<?php
namespace Modules\Inventory\Domain\Contracts;
interface ProductRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findBySku(string $sku): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
}
