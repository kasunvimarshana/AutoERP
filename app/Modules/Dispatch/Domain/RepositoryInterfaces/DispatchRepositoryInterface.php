<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;

interface DispatchRepositoryInterface extends RepositoryInterface
{
    public function save(Dispatch $dispatch): Dispatch;
    public function findById(int $id): ?Dispatch;
    public function delete(mixed $id): bool;
    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed;
    public function findByWarehouse(int $tenantId, int $warehouseId): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
    public function findBySalesOrder(int $tenantId, int $salesOrderId): Collection;
    public function findByReferenceNumber(int $tenantId, string $referenceNumber): ?Dispatch;
}
