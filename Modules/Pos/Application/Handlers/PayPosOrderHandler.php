<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Pos\Application\Commands\PayPosOrderCommand;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosOrder;
use Modules\Pos\Domain\Entities\PosPayment;
use Modules\Pos\Domain\Entities\PosSession;
use Modules\Pos\Domain\Enums\PosOrderStatus;

class PayPosOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PosOrderRepositoryInterface $orderRepository,
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(PayPosOrderCommand $command): PosOrder
    {
        return $this->transaction(function () use ($command): PosOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (PayPosOrderCommand $cmd): PosOrder {
                    $order = $this->orderRepository->findById($cmd->id, $cmd->tenantId);
                    if ($order === null) {
                        throw new \DomainException("POS order with ID '{$cmd->id}' not found.");
                    }
                    if ($order->status !== PosOrderStatus::Draft->value) {
                        throw new \DomainException("POS order '{$cmd->id}' cannot be paid (status: {$order->status}).");
                    }

                    $totalPaid = '0.0000';
                    foreach ($cmd->payments as $p) {
                        $totalPaid = bcadd($totalPaid, (string) ($p['amount'] ?? '0'), 4);
                    }

                    $change = bcsub($totalPaid, $order->totalAmount, 4);
                    if (bccomp($change, '0', 4) < 0) {
                        throw new \DomainException('Insufficient payment amount.');
                    }

                    // Save payment records
                    foreach ($cmd->payments as $p) {
                        $this->orderRepository->savePayment(new PosPayment(
                            id: null,
                            tenantId: $cmd->tenantId,
                            posOrderId: (int) $order->id,
                            method: (string) ($p['method'] ?? 'cash'),
                            amount: (string) ($p['amount'] ?? '0'),
                            currency: (string) ($p['currency'] ?? $order->currency),
                            reference: $p['reference'] ?? null,
                            createdAt: null,
                            updatedAt: null,
                        ));
                    }

                    // Update order status
                    $updatedOrder = $this->orderRepository->save(new PosOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        posSessionId: $order->posSessionId,
                        reference: $order->reference,
                        status: PosOrderStatus::Paid->value,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        paidAmount: $totalPaid,
                        changeAmount: $change,
                        notes: $order->notes,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    ));

                    // Update session total_sales
                    $session = $this->sessionRepository->findById($order->posSessionId, $cmd->tenantId);
                    if ($session !== null) {
                        $newTotalSales = bcadd($session->totalSales, $order->totalAmount, 4);
                        $this->sessionRepository->save(new PosSession(
                            id: $session->id,
                            tenantId: $session->tenantId,
                            userId: $session->userId,
                            reference: $session->reference,
                            status: $session->status,
                            openedAt: $session->openedAt,
                            closedAt: $session->closedAt,
                            currency: $session->currency,
                            openingFloat: $session->openingFloat,
                            closingFloat: $session->closingFloat,
                            totalSales: $newTotalSales,
                            totalRefunds: $session->totalRefunds,
                            notes: $session->notes,
                            createdAt: $session->createdAt,
                            updatedAt: null,
                        ));
                    }

                    return $updatedOrder;
                });
        });
    }
}
