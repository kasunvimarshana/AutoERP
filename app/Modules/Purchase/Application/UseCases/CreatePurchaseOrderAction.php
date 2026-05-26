<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Application\DTOs\CreatePurchaseOrderDTO;
use Modules\Purchase\Domain\Services\PurchaseOrderService;

final class CreatePurchaseOrderAction
{
    public function __construct(private readonly PurchaseOrderService $service)
    {
    }

    public function execute(CreatePurchaseOrderDTO $dto): array
    {
        return $this->service->create($dto->payload);
    }
}
