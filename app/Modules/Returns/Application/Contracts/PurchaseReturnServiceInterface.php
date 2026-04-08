<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\PurchaseReturn;

interface PurchaseReturnServiceInterface
{
    public function getPurchaseReturn(string $tenantId, string $id): PurchaseReturn;

    public function getAllPurchaseReturns(string $tenantId): array;

    public function createPurchaseReturn(string $tenantId, array $data): PurchaseReturn;

    public function approvePurchaseReturn(string $tenantId, string $id): PurchaseReturn;

    public function completePurchaseReturn(string $tenantId, string $id): PurchaseReturn;

    public function cancelPurchaseReturn(string $tenantId, string $id): PurchaseReturn;
}
