<?php
namespace Modules\PurchaseOrder\Domain\RepositoryInterfaces;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
interface PurchaseOrderRepositoryInterface
{
    public function findById(int $id): ?PurchaseOrder;
    public function findByPoNumber(int $tenantId, string $poNumber): ?PurchaseOrder;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): PurchaseOrder;
    public function update(PurchaseOrder $po, array $data): PurchaseOrder;
    public function save(PurchaseOrder $po): PurchaseOrder;
}
