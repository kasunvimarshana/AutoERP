<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Application\DTOs\CreatePurchaseInvoiceDTO;
use Modules\Purchase\Domain\Services\PurchaseInvoiceService;

final class CreatePurchaseInvoiceAction
{
    public function __construct(private readonly PurchaseInvoiceService $service)
    {
    }

    public function execute(CreatePurchaseInvoiceDTO $dto): array
    {
        return $this->service->create($dto->payload);
    }

    public function approve(int $invoiceId): array
    {
        return $this->service->approve($invoiceId);
    }
}
