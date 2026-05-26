<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Orchestrators;

use Modules\Purchase\Application\DTOs\CreatePurchaseInvoiceDTO;
use Modules\Purchase\Application\DTOs\CreatePurchaseOrderDTO;
use Modules\Purchase\Application\DTOs\CreatePurchasePaymentDTO;
use Modules\Purchase\Application\DTOs\CreatePurchaseReturnDTO;
use Modules\Purchase\Application\DTOs\CreateAdvancePaymentDTO;
use Modules\Purchase\Application\DTOs\ReceiveGoodsDTO;
use Modules\Purchase\Application\UseCases\ConfirmPurchaseOrderAction;
use Modules\Purchase\Application\UseCases\CreateAdvancePaymentAction;
use Modules\Purchase\Application\UseCases\CreatePurchaseInvoiceAction;
use Modules\Purchase\Application\UseCases\CreatePurchaseOrderAction;
use Modules\Purchase\Application\UseCases\CreatePurchasePaymentAction;
use Modules\Purchase\Application\UseCases\CreatePurchaseReturnAction;
use Modules\Purchase\Application\UseCases\ReceiveGoodsAction;

final class PurchaseOrchestrator
{
    public function __construct(
        private readonly CreatePurchaseOrderAction $createPurchaseOrder,
        private readonly ConfirmPurchaseOrderAction $confirmPurchaseOrder,
        private readonly ReceiveGoodsAction $receiveGoods,
        private readonly CreatePurchaseInvoiceAction $createPurchaseInvoice,
        private readonly CreatePurchasePaymentAction $createPurchasePayment,
        private readonly CreatePurchaseReturnAction $createPurchaseReturn,
        private readonly CreateAdvancePaymentAction $createAdvancePayment,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function createPurchaseOrder(array $payload): array
    {
        return $this->createPurchaseOrder->execute(new CreatePurchaseOrderDTO($payload));
    }

    public function confirmPurchaseOrder(int $purchaseOrderId): array
    {
        return $this->confirmPurchaseOrder->execute($purchaseOrderId);
    }

    /** @param array<string, mixed> $payload */
    public function receiveGoods(array $payload): array
    {
        return $this->receiveGoods->execute(new ReceiveGoodsDTO($payload));
    }

    /** @param array<string, mixed> $payload */
    public function createPurchaseInvoice(array $payload): array
    {
        return $this->createPurchaseInvoice->execute(new CreatePurchaseInvoiceDTO($payload));
    }

    /** @param array<string, mixed> $payload */
    public function createPurchasePayment(array $payload): array
    {
        return $this->createPurchasePayment->execute(new CreatePurchasePaymentDTO($payload));
    }

    /** @param array<string, mixed> $payload */
    public function createPurchaseReturn(array $payload): array
    {
        return $this->createPurchaseReturn->execute(new CreatePurchaseReturnDTO($payload));
    }

    /** @param array<string, mixed> $payload */
    public function createAdvancePayment(array $payload): array
    {
        return $this->createAdvancePayment->execute(new CreateAdvancePaymentDTO($payload));
    }
}
