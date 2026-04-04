<?php
namespace Modules\PurchaseOrder\Domain\RepositoryInterfaces;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;
interface PurchaseOrderLineRepositoryInterface
{
    public function findById(int $id): ?PurchaseOrderLine;
    public function findByPurchaseOrder(int $purchaseOrderId): array;
    public function create(array $data): PurchaseOrderLine;
    public function update(PurchaseOrderLine $line, array $data): PurchaseOrderLine;
}
