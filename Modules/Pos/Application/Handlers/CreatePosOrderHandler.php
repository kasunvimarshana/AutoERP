<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Pos\Application\Commands\CreatePosOrderCommand;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosOrder;
use Modules\Pos\Domain\Entities\PosOrderLine;
use Modules\Pos\Domain\Enums\PosOrderStatus;
use Modules\Pos\Domain\Enums\PosSessionStatus;

class CreatePosOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PosOrderRepositoryInterface $orderRepository,
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreatePosOrderCommand $command): PosOrder
    {
        return $this->transaction(function () use ($command): PosOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreatePosOrderCommand $cmd): PosOrder {
                    $session = $this->sessionRepository->findById($cmd->posSessionId, $cmd->tenantId);
                    if ($session === null) {
                        throw new \DomainException("POS session with ID '{$cmd->posSessionId}' not found.");
                    }
                    if ($session->status !== PosSessionStatus::Open->value) {
                        throw new \DomainException("POS session '{$cmd->posSessionId}' is not open.");
                    }

                    $subtotal = '0.0000';
                    $taxAmount = '0.0000';
                    $discountAmount = '0.0000';
                    $lineEntities = [];

                    foreach ($cmd->lines as $line) {
                        $qty = (string) ($line['quantity'] ?? '0');
                        $unitPrice = (string) ($line['unit_price'] ?? '0');
                        $lineDiscount = (string) ($line['discount_amount'] ?? '0');
                        $lineTax = (string) ($line['tax_amount'] ?? '0');

                        $lineTotal = bcmul($qty, $unitPrice, 4);
                        $lineTotal = bcsub($lineTotal, $lineDiscount, 4);
                        $lineTotal = bcadd($lineTotal, $lineTax, 4);

                        $subtotal = bcadd($subtotal, bcmul($qty, $unitPrice, 4), 4);
                        $taxAmount = bcadd($taxAmount, $lineTax, 4);
                        $discountAmount = bcadd($discountAmount, $lineDiscount, 4);

                        $lineEntities[] = new PosOrderLine(
                            id: null,
                            tenantId: $cmd->tenantId,
                            posOrderId: 0,
                            productId: (int) ($line['product_id'] ?? 0),
                            productName: (string) ($line['product_name'] ?? ''),
                            sku: (string) ($line['sku'] ?? ''),
                            quantity: $qty,
                            unitPrice: $unitPrice,
                            discountAmount: $lineDiscount,
                            taxAmount: $lineTax,
                            lineTotal: $lineTotal,
                            createdAt: null,
                            updatedAt: null,
                        );
                    }

                    $totalAmount = bcadd($subtotal, $taxAmount, 4);
                    $totalAmount = bcsub($totalAmount, $discountAmount, 4);

                    $reference = 'POS-ORD-'.date('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

                    $order = $this->orderRepository->save(new PosOrder(
                        id: null,
                        tenantId: $cmd->tenantId,
                        posSessionId: $cmd->posSessionId,
                        reference: $reference,
                        status: PosOrderStatus::Draft->value,
                        currency: $cmd->currency,
                        subtotal: $subtotal,
                        taxAmount: $taxAmount,
                        discountAmount: $discountAmount,
                        totalAmount: $totalAmount,
                        paidAmount: '0.0000',
                        changeAmount: '0.0000',
                        notes: $cmd->notes,
                        createdAt: null,
                        updatedAt: null,
                    ));

                    // Update line entities with actual order id and save
                    $linesWithOrderId = array_map(function (PosOrderLine $l) use ($order, $cmd): PosOrderLine {
                        return new PosOrderLine(
                            id: null,
                            tenantId: $cmd->tenantId,
                            posOrderId: (int) $order->id,
                            productId: $l->productId,
                            productName: $l->productName,
                            sku: $l->sku,
                            quantity: $l->quantity,
                            unitPrice: $l->unitPrice,
                            discountAmount: $l->discountAmount,
                            taxAmount: $l->taxAmount,
                            lineTotal: $l->lineTotal,
                            createdAt: null,
                            updatedAt: null,
                        );
                    }, $lineEntities);

                    $this->orderRepository->saveLines((int) $order->id, $linesWithOrderId);

                    return $order;
                });
        });
    }
}
