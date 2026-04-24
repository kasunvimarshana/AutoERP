<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Application\Contracts\FindBatchServiceInterface;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchRepositoryInterface;

class FindBatchService implements FindBatchServiceInterface
{
    public function __construct(private readonly BatchRepositoryInterface $batchRepository) {}

    public function findById(int $id): ?Batch
    {
        return $this->batchRepository->find($id);
    }

    public function list(
        array $filters,
        int $perPage,
        int $page,
        string $sort,
    ): LengthAwarePaginator {
        return $this->batchRepository->findByTenant(
            tenantId: (int) ($filters['tenant_id'] ?? 0),
            filters: $filters,
            perPage: $perPage,
            page: $page,
            sort: $sort,
        );
    }
}
