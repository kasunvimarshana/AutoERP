<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Application\DTOs\CreateAdvancePaymentDTO;
use Modules\Purchase\Domain\Services\PurchaseAdvancePaymentService;

final class CreateAdvancePaymentAction
{
    public function __construct(private readonly PurchaseAdvancePaymentService $service)
    {
    }

    public function execute(CreateAdvancePaymentDTO $dto): array
    {
        return $this->service->record($dto->payload);
    }

    /** @param array<string, mixed> $payload */
    public function allocate(int $advancePaymentId, array $payload): array
    {
        return $this->service->allocate($advancePaymentId, $payload);
    }
}
