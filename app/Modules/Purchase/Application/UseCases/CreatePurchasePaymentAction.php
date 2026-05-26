<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Application\DTOs\CreatePurchasePaymentDTO;
use Modules\Purchase\Domain\Services\PurchasePaymentService;

final class CreatePurchasePaymentAction
{
    public function __construct(private readonly PurchasePaymentService $service)
    {
    }

    public function execute(CreatePurchasePaymentDTO $dto): array
    {
        return $this->service->create($dto->payload);
    }
}
