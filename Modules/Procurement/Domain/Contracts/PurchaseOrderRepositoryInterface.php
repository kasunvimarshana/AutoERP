<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Contracts;

use Modules\Procurement\Domain\Entities\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?PurchaseOrder;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function findByOrderNumber(string $orderNumber, int $tenantId): ?PurchaseOrder;

    public function save(PurchaseOrder $order): PurchaseOrder;

    public function delete(int $id, int $tenantId): void;

    public function nextOrderNumber(int $tenantId): string;
}
