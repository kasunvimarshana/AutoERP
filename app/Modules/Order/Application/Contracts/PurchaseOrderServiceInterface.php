<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Order\Domain\Entities\PurchaseOrder;

interface PurchaseOrderServiceInterface
{
    public function getPurchaseOrder(string $tenantId, string $id): PurchaseOrder;

    /** @return PurchaseOrder[] */
    public function getAllPurchaseOrders(string $tenantId): array;

    public function createPurchaseOrder(string $tenantId, array $data): PurchaseOrder;

    public function confirmPurchaseOrder(string $tenantId, string $id): PurchaseOrder;

    public function cancelPurchaseOrder(string $tenantId, string $id): PurchaseOrder;

    public function updatePurchaseOrder(string $tenantId, string $id, array $data): PurchaseOrder;

    public function deletePurchaseOrder(string $tenantId, string $id): void;
}
