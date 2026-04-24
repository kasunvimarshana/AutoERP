<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\Batch;

interface FindBatchServiceInterface
{
    public function findById(int $id): ?Batch;

    public function list(
        array $filters,
        int $perPage,
        int $page,
        string $sort,
    ): LengthAwarePaginator;
}
