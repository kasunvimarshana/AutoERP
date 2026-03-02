<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Sales\Application\Commands\CreateSalesOrderCommand;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Domain\Enums\SalesOrderStatus;

class CreateSalesOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateSalesOrderCommand $command): SalesOrder
    {
        return $this->transaction(function () use ($command): SalesOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateSalesOrderCommand $cmd): SalesOrder {
                    if (empty($cmd->lines)) {
                        throw new \DomainException('A sales order must have at least one line item.');
                    }

                    $orderNumber = $this->salesOrderRepository->nextOrderNumber($cmd->tenantId);

                    [$subtotal, $taxAmount, $discountAmount, $totalAmount, $lines] = $this->calculateTotals($cmd);

                    $order = new SalesOrder(
                        id: null,
                        tenantId: $cmd->tenantId,
                        orderNumber: $orderNumber,
                        customerName: $cmd->customerName,
                        customerEmail: $cmd->customerEmail,
                        customerPhone: $cmd->customerPhone,
                        status: SalesOrderStatus::Draft->value,
                        orderDate: $cmd->orderDate,
                        dueDate: $cmd->dueDate,
                        notes: $cmd->notes,
                        currency: $cmd->currency,
                        subtotal: $subtotal,
                        taxAmount: $taxAmount,
                        discountAmount: $discountAmount,
                        totalAmount: $totalAmount,
                        lines: $lines,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->salesOrderRepository->save($order);
                });
        });
    }

    /**
     * Calculate order totals using BCMath for financial precision.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: SalesOrderLine[]}
     */
    private function calculateTotals(CreateSalesOrderCommand $cmd): array
    {
        $subtotal = '0.0000';
        $taxAmount = '0.0000';
        $discountAmount = '0.0000';
        $lines = [];

        foreach ($cmd->lines as $lineData) {
            $quantity = bcadd((string) $lineData['quantity'], '0', 4);
            $unitPrice = bcadd((string) $lineData['unit_price'], '0', 4);
            $taxRate = bcadd((string) ($lineData['tax_rate'] ?? '0'), '0', 4);
            $discountRate = bcadd((string) ($lineData['discount_rate'] ?? '0'), '0', 4);

            $gross = bcmul($quantity, $unitPrice, 4);
            $discountAmt = bcmul($gross, bcdiv($discountRate, '100', 4), 4);
            $afterDisc = bcsub($gross, $discountAmt, 4);
            $taxAmt = bcmul($afterDisc, bcdiv($taxRate, '100', 4), 4);
            $lineTotal = bcadd($afterDisc, $taxAmt, 4);

            $subtotal = bcadd($subtotal, $afterDisc, 4);
            $taxAmount = bcadd($taxAmount, $taxAmt, 4);
            $discountAmount = bcadd($discountAmount, $discountAmt, 4);

            $lines[] = new SalesOrderLine(
                id: null,
                salesOrderId: 0,
                productId: (int) $lineData['product_id'],
                description: $lineData['description'] ?? null,
                quantity: $quantity,
                unitPrice: $unitPrice,
                taxRate: $taxRate,
                discountRate: $discountRate,
                lineTotal: $lineTotal,
                createdAt: null,
                updatedAt: null,
            );
        }

        $totalAmount = bcadd($subtotal, $taxAmount, 4);

        return [$subtotal, $taxAmount, $discountAmount, $totalAmount, $lines];
    }
}
