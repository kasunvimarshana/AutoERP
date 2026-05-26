<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Application\DTOs\CreatePurchaseReturnDTO;
use Modules\Purchase\Domain\Services\PurchaseReturnService;

final class CreatePurchaseReturnAction
{
    public function __construct(private readonly PurchaseReturnService $service)
    {
    }

    public function execute(CreatePurchaseReturnDTO $dto): array
    {
        return $this->service->create($dto->payload);
    }

    public function approve(int $purchaseReturnId): array
    {
        return $this->service->approve($purchaseReturnId);
    }
}
