<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Domain\Services\PurchaseReversalService;

final class ReverseTransactionAction
{
    public function __construct(private readonly PurchaseReversalService $service)
    {
    }

    public function cancelInvoice(int $invoiceId): array
    {
        return $this->service->cancelInvoice($invoiceId);
    }

    public function voidPayment(int $paymentId): array
    {
        return $this->service->voidPayment($paymentId);
    }
}
