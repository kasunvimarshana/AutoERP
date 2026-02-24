<?php
namespace Modules\Inventory\Domain\Contracts;
interface StockMovementRepositoryInterface
{
    public function findById(string $id): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
}
