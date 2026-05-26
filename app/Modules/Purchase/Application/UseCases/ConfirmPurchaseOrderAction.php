<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Domain\Services\PurchaseOrderService;

final class ConfirmPurchaseOrderAction
{
    public function __construct(private readonly PurchaseOrderService $service)
    {
    }

    public function execute(int $purchaseOrderId): array
    {
        return $this->service->confirm($purchaseOrderId);
    }
}
