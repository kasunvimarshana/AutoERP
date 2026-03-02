<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Procurement\Application\Commands\ConfirmPurchaseOrderCommand;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Enums\PurchaseOrderStatus;

class ConfirmPurchaseOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(ConfirmPurchaseOrderCommand $command): PurchaseOrder
    {
        return $this->transaction(function () use ($command): PurchaseOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (ConfirmPurchaseOrderCommand $cmd): PurchaseOrder {
                    $order = $this->purchaseOrderRepository->findById($cmd->id, $cmd->tenantId);

                    if ($order === null) {
                        throw new \DomainException("Purchase order with ID {$cmd->id} not found.");
                    }

                    if ($order->status !== PurchaseOrderStatus::Draft->value) {
                        throw new \DomainException(
                            "Purchase order can only be confirmed from draft status. Current status: {$order->status}."
                        );
                    }

                    $confirmed = new PurchaseOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        supplierId: $order->supplierId,
                        orderNumber: $order->orderNumber,
                        status: PurchaseOrderStatus::Confirmed->value,
                        orderDate: $order->orderDate,
                        expectedDeliveryDate: $order->expectedDeliveryDate,
                        notes: $order->notes,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        lines: $order->lines,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    );

                    return $this->purchaseOrderRepository->save($confirmed);
                });
        });
    }
}
