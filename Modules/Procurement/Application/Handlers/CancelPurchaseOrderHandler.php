<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Procurement\Application\Commands\CancelPurchaseOrderCommand;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Enums\PurchaseOrderStatus;

class CancelPurchaseOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CancelPurchaseOrderCommand $command): PurchaseOrder
    {
        return $this->transaction(function () use ($command): PurchaseOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CancelPurchaseOrderCommand $cmd): PurchaseOrder {
                    $order = $this->purchaseOrderRepository->findById($cmd->id, $cmd->tenantId);

                    if ($order === null) {
                        throw new \DomainException("Purchase order with ID {$cmd->id} not found.");
                    }

                    $status = PurchaseOrderStatus::from($order->status);

                    if (! $status->isCancellable()) {
                        throw new \DomainException(
                            "Purchase order cannot be cancelled in status '{$order->status}'."
                        );
                    }

                    $notes = $order->notes;
                    if ($cmd->reason !== null && $cmd->reason !== '') {
                        $notes = trim(($notes ?? '')."\nCancellation reason: {$cmd->reason}");
                    }

                    $cancelled = new PurchaseOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        supplierId: $order->supplierId,
                        orderNumber: $order->orderNumber,
                        status: PurchaseOrderStatus::Cancelled->value,
                        orderDate: $order->orderDate,
                        expectedDeliveryDate: $order->expectedDeliveryDate,
                        notes: $notes,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        lines: $order->lines,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    );

                    return $this->purchaseOrderRepository->save($cancelled);
                });
        });
    }
}
