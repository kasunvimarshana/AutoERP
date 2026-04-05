<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

interface UnitOfMeasureRepositoryInterface
{
    public function findById(int $id): ?UnitOfMeasure;
    public function findByCategory(int $categoryId): array;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findBaseUnit(int $categoryId): ?UnitOfMeasure;
    public function create(array $data): UnitOfMeasure;
    public function update(int $id, array $data): ?UnitOfMeasure;
    public function delete(int $id): bool;
}
