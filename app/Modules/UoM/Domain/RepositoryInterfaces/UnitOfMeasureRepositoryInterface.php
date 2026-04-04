<?php
namespace Modules\UoM\Domain\RepositoryInterfaces;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

interface UnitOfMeasureRepositoryInterface
{
    public function findById(int $id): ?UnitOfMeasure;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByCategory(int $categoryId): array;
    public function create(array $data): UnitOfMeasure;
    public function update(UnitOfMeasure $uom, array $data): UnitOfMeasure;
    public function delete(UnitOfMeasure $uom): bool;
}
