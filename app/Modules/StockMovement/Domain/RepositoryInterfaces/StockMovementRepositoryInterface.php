<?php
namespace Modules\StockMovement\Domain\RepositoryInterfaces;
use Modules\StockMovement\Domain\Entities\StockMovement;
interface StockMovementRepositoryInterface
{
    public function findById(int $id): ?StockMovement;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function findByReference(string $referenceNumber): ?StockMovement;
    public function create(array $data): StockMovement;
    public function update(StockMovement $movement, array $data): StockMovement;
}
