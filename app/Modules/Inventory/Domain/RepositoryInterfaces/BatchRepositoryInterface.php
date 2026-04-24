<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\Batch;

interface BatchRepositoryInterface
{
    public function save(Batch $batch): Batch;

    public function find(int $id): ?Batch;

    public function delete(int $id): bool;

    public function findByTenant(
        int $tenantId,
        array $filters,
        int $perPage,
        int $page,
        string $sort,
    ): LengthAwarePaginator;
}
