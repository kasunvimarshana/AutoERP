<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Domain\Entities\UomCategory;

interface UomCategoryRepositoryInterface
{
    public function findById(int $id): ?UomCategory;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): UomCategory;
    public function update(int $id, array $data): ?UomCategory;
    public function delete(int $id): bool;
}
