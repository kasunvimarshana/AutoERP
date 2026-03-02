<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Procurement\Application\Commands\CreatePurchaseOrderCommand;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Entities\PurchaseOrderLine;
use Modules\Procurement\Domain\Enums\PurchaseOrderStatus;

class CreatePurchaseOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreatePurchaseOrderCommand $command): PurchaseOrder
    {
        return $this->transaction(function () use ($command): PurchaseOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreatePurchaseOrderCommand $cmd): PurchaseOrder {
                    if (empty($cmd->lines)) {
                        throw new \DomainException('A purchase order must have at least one line item.');
                    }

                    $supplier = $this->supplierRepository->findById($cmd->supplierId, $cmd->tenantId);

                    if ($supplier === null) {
                        throw new \DomainException("Supplier with ID {$cmd->supplierId} not found.");
                    }

                    $orderNumber = $this->purchaseOrderRepository->nextOrderNumber($cmd->tenantId);

                    [$subtotal, $taxAmount, $discountAmount, $totalAmount, $lines] = $this->calculateTotals($cmd);

                    $order = new PurchaseOrder(
                        id: null,
                        tenantId: $cmd->tenantId,
                        supplierId: $cmd->supplierId,
                        orderNumber: $orderNumber,
                        status: PurchaseOrderStatus::Draft->value,
                        orderDate: $cmd->orderDate,
                        expectedDeliveryDate: $cmd->expectedDeliveryDate,
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

                    return $this->purchaseOrderRepository->save($order);
                });
        });
    }

    /**
     * Calculate order totals using BCMath for financial precision.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: PurchaseOrderLine[]}
     */
    private function calculateTotals(CreatePurchaseOrderCommand $cmd): array
    {
        $subtotal = '0.0000';
        $taxAmount = '0.0000';
        $discountAmount = '0.0000';
        $lines = [];

        foreach ($cmd->lines as $lineData) {
            $quantity = bcadd((string) $lineData['quantity'], '0', 4);
            $unitCost = bcadd((string) $lineData['unit_cost'], '0', 4);
            $taxRate = bcadd((string) ($lineData['tax_rate'] ?? '0'), '0', 4);
            $discountRate = bcadd((string) ($lineData['discount_rate'] ?? '0'), '0', 4);

            $gross = bcmul($quantity, $unitCost, 4);
            $discountAmt = bcmul($gross, bcdiv($discountRate, '100', 4), 4);
            $afterDisc = bcsub($gross, $discountAmt, 4);
            $taxAmt = bcmul($afterDisc, bcdiv($taxRate, '100', 4), 4);
            $lineTotal = bcadd($afterDisc, $taxAmt, 4);

            $subtotal = bcadd($subtotal, $afterDisc, 4);
            $taxAmount = bcadd($taxAmount, $taxAmt, 4);
            $discountAmount = bcadd($discountAmount, $discountAmt, 4);

            $lines[] = new PurchaseOrderLine(
                id: null,
                purchaseOrderId: 0,
                productId: (int) $lineData['product_id'],
                description: $lineData['description'] ?? null,
                quantityOrdered: $quantity,
                quantityReceived: '0.0000',
                unitCost: $unitCost,
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
