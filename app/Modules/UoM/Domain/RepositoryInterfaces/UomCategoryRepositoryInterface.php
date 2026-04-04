<?php
namespace Modules\UoM\Domain\RepositoryInterfaces;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UomCategory;

interface UomCategoryRepositoryInterface
{
    public function findById(int $id): ?UomCategory;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): UomCategory;
    public function update(UomCategory $category, array $data): UomCategory;
    public function delete(UomCategory $category): bool;
}
