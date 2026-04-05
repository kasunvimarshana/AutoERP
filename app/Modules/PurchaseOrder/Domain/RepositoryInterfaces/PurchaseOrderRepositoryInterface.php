<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface PurchaseOrderRepositoryInterface {
    public function findById(int $id): ?PurchaseOrder;
    public function findByPoNumber(int $tenantId, string $poNumber): ?PurchaseOrder;
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data, array $lines): PurchaseOrder;
    public function update(int $id, array $data): ?PurchaseOrder;
    public function updateStatus(int $id, string $status): bool;
    public function delete(int $id): bool;
}
