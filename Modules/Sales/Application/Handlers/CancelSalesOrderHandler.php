<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Sales\Application\Commands\CancelSalesOrderCommand;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Enums\SalesOrderStatus;

class CancelSalesOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CancelSalesOrderCommand $command): SalesOrder
    {
        return $this->transaction(function () use ($command): SalesOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CancelSalesOrderCommand $cmd): SalesOrder {
                    $order = $this->salesOrderRepository->findById($cmd->id, $cmd->tenantId);

                    if ($order === null) {
                        throw new \DomainException('Sales order not found.');
                    }

                    if ($order->status === SalesOrderStatus::Cancelled->value) {
                        throw new \DomainException('Sales order is already cancelled.');
                    }

                    if ($order->status === SalesOrderStatus::Invoiced->value) {
                        throw new \DomainException('Invoiced orders cannot be cancelled.');
                    }

                    $notes = $order->notes;
                    if ($cmd->reason !== null) {
                        $notes = trim(($notes ?? '')."\nCancellation reason: {$cmd->reason}");
                    }

                    $cancelled = new SalesOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        orderNumber: $order->orderNumber,
                        customerName: $order->customerName,
                        customerEmail: $order->customerEmail,
                        customerPhone: $order->customerPhone,
                        status: SalesOrderStatus::Cancelled->value,
                        orderDate: $order->orderDate,
                        dueDate: $order->dueDate,
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

                    return $this->salesOrderRepository->save($cancelled);
                });
        });
    }
}
