<?php

declare(strict_types=1);

namespace Modules\Order\Domain\RepositoryInterfaces;

use Modules\Order\Domain\Entities\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PurchaseOrder;

    /** @return PurchaseOrder[] */
    public function findAll(string $tenantId): array;

    /** @return PurchaseOrder[] */
    public function findBySupplier(string $tenantId, string $supplierId): array;

    /** @return PurchaseOrder[] */
    public function findByStatus(string $tenantId, string $status): array;

    public function findByReference(string $tenantId, string $reference): ?PurchaseOrder;

    public function save(PurchaseOrder $purchaseOrder): void;

    public function delete(string $tenantId, string $id): void;
}
