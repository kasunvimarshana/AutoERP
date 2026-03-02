<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Pos\Application\Commands\CancelPosOrderCommand;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Entities\PosOrder;
use Modules\Pos\Domain\Enums\PosOrderStatus;

class CancelPosOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PosOrderRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CancelPosOrderCommand $command): PosOrder
    {
        return $this->transaction(function () use ($command): PosOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CancelPosOrderCommand $cmd): PosOrder {
                    $order = $this->repository->findById($cmd->id, $cmd->tenantId);
                    if ($order === null) {
                        throw new \DomainException("POS order with ID '{$cmd->id}' not found.");
                    }
                    if ($order->status !== PosOrderStatus::Draft->value) {
                        throw new \DomainException("Only draft POS orders can be cancelled (status: {$order->status}).");
                    }

                    return $this->repository->save(new PosOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        posSessionId: $order->posSessionId,
                        reference: $order->reference,
                        status: PosOrderStatus::Cancelled->value,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        paidAmount: $order->paidAmount,
                        changeAmount: $order->changeAmount,
                        notes: $order->notes,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
