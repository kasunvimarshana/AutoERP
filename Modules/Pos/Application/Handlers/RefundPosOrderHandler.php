<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Pos\Application\Commands\RefundPosOrderCommand;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosOrder;
use Modules\Pos\Domain\Entities\PosPayment;
use Modules\Pos\Domain\Entities\PosSession;
use Modules\Pos\Domain\Enums\PosOrderStatus;

class RefundPosOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PosOrderRepositoryInterface $orderRepository,
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(RefundPosOrderCommand $command): PosOrder
    {
        return $this->transaction(function () use ($command): PosOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (RefundPosOrderCommand $cmd): PosOrder {
                    $order = $this->orderRepository->findById($cmd->id, $cmd->tenantId);
                    if ($order === null) {
                        throw new \DomainException("POS order with ID '{$cmd->id}' not found.");
                    }
                    if ($order->status !== PosOrderStatus::Paid->value && $order->status !== PosOrderStatus::PartialRefund->value) {
                        throw new \DomainException("POS order '{$cmd->id}' cannot be refunded (status: {$order->status}).");
                    }

                    if (bccomp($cmd->refundAmount, '0', 4) <= 0) {
                        throw new \DomainException('Refund amount must be greater than zero.');
                    }

                    $refundAmountBc = bcadd($cmd->refundAmount, '0', 4);
                    if (bccomp($refundAmountBc, $order->totalAmount, 4) > 0) {
                        throw new \DomainException('Refund amount cannot exceed order total.');
                    }

                    // Determine new status
                    $newStatus = bccomp($refundAmountBc, $order->totalAmount, 4) === 0
                        ? PosOrderStatus::Refunded->value
                        : PosOrderStatus::PartialRefund->value;

                    // Save refund payment (negative)
                    $this->orderRepository->savePayment(new PosPayment(
                        id: null,
                        tenantId: $cmd->tenantId,
                        posOrderId: (int) $order->id,
                        method: $cmd->method,
                        amount: '-'.$refundAmountBc,
                        currency: $order->currency,
                        reference: 'REFUND',
                        createdAt: null,
                        updatedAt: null,
                    ));

                    $updatedOrder = $this->orderRepository->save(new PosOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        posSessionId: $order->posSessionId,
                        reference: $order->reference,
                        status: $newStatus,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        paidAmount: $order->paidAmount,
                        changeAmount: $order->changeAmount,
                        notes: $cmd->notes ?? $order->notes,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    ));

                    // Update session total_refunds
                    $session = $this->sessionRepository->findById($order->posSessionId, $cmd->tenantId);
                    if ($session !== null) {
                        $newTotalRefunds = bcadd($session->totalRefunds, $refundAmountBc, 4);
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
                            totalSales: $session->totalSales,
                            totalRefunds: $newTotalRefunds,
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
