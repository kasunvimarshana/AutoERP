<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\ReceiveStockCommand;
use Modules\Inventory\Application\Handlers\ReceiveStockHandler;
use Modules\Procurement\Application\Commands\ReceiveGoodsCommand;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Entities\PurchaseOrderLine;
use Modules\Procurement\Domain\Enums\PurchaseOrderStatus;

class ReceiveGoodsHandler extends BaseHandler
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly ReceiveStockHandler $receiveStockHandler,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(ReceiveGoodsCommand $command): PurchaseOrder
    {
        return $this->transaction(function () use ($command): PurchaseOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (ReceiveGoodsCommand $cmd): PurchaseOrder {
                    $order = $this->purchaseOrderRepository->findById($cmd->id, $cmd->tenantId);

                    if ($order === null) {
                        throw new \DomainException("Purchase order with ID {$cmd->id} not found.");
                    }

                    $status = PurchaseOrderStatus::from($order->status);

                    if (! $status->isReceivable()) {
                        throw new \DomainException(
                            "Purchase order cannot be received in status '{$order->status}'. "
                            .'Only confirmed or partially_received orders can receive goods.'
                        );
                    }

                    if (empty($cmd->receivedLines)) {
                        throw new \DomainException('At least one line must be specified for goods receipt.');
                    }

                    $lineMap = [];
                    foreach ($order->lines as $line) {
                        $lineMap[$line->id] = $line;
                    }

                    $updatedLines = $order->lines;

                    foreach ($cmd->receivedLines as $receivedData) {
                        $lineId = (int) $receivedData['line_id'];
                        $quantityReceived = bcadd((string) $receivedData['quantity_received'], '0', 4);

                        if (! isset($lineMap[$lineId])) {
                            throw new \DomainException(
                                "Purchase order line with ID {$lineId} not found on this order."
                            );
                        }

                        $line = $lineMap[$lineId];

                        if (bccomp($quantityReceived, '0', 4) <= 0) {
                            throw new \DomainException(
                                "Received quantity for line {$lineId} must be greater than zero."
                            );
                        }

                        $newTotalReceived = bcadd($line->quantityReceived, $quantityReceived, 4);

                        if (bccomp($newTotalReceived, $line->quantityOrdered, 4) > 0) {
                            throw new \DomainException(
                                "Cannot receive more than ordered quantity on line {$lineId}. "
                                ."Ordered: {$line->quantityOrdered}, already received: {$line->quantityReceived}, "
                                ."attempted: {$quantityReceived}."
                            );
                        }

                        $this->receiveStockHandler->handle(new ReceiveStockCommand(
                            tenantId: $cmd->tenantId,
                            warehouseId: $cmd->warehouseId,
                            productId: $line->productId,
                            quantity: $quantityReceived,
                            unitCost: $line->unitCost,
                            referenceType: 'purchase_order',
                            referenceId: (string) $order->id,
                            notes: $cmd->notes,
                        ));

                        $updatedLine = new PurchaseOrderLine(
                            id: $line->id,
                            purchaseOrderId: $line->purchaseOrderId,
                            productId: $line->productId,
                            description: $line->description,
                            quantityOrdered: $line->quantityOrdered,
                            quantityReceived: $newTotalReceived,
                            unitCost: $line->unitCost,
                            taxRate: $line->taxRate,
                            discountRate: $line->discountRate,
                            lineTotal: $line->lineTotal,
                            createdAt: $line->createdAt,
                            updatedAt: null,
                        );
                        $lineMap[$lineId] = $updatedLine;
                    }

                    $updatedLines = array_values($lineMap);

                    $updatedOrder = new PurchaseOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        supplierId: $order->supplierId,
                        orderNumber: $order->orderNumber,
                        status: $this->resolveNewStatus($updatedLines),
                        orderDate: $order->orderDate,
                        expectedDeliveryDate: $order->expectedDeliveryDate,
                        notes: $order->notes,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        lines: $updatedLines,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    );

                    return $this->purchaseOrderRepository->save($updatedOrder);
                });
        });
    }

    /**
     * Determine whether the order is fully or only partially received.
     *
     * @param  PurchaseOrderLine[]  $lines
     */
    private function resolveNewStatus(array $lines): string
    {
        foreach ($lines as $line) {
            if (! $line->isFullyReceived()) {
                return PurchaseOrderStatus::PartiallyReceived->value;
            }
        }

        return PurchaseOrderStatus::Received->value;
    }
}
