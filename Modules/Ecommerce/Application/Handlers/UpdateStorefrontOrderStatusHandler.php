<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\UpdateStorefrontOrderStatusCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontOrderRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontOrder;
use Modules\Ecommerce\Domain\Enums\StorefrontOrderStatus;

class UpdateStorefrontOrderStatusHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontOrderRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateStorefrontOrderStatusCommand $command): StorefrontOrder
    {
        return $this->transaction(function () use ($command): StorefrontOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateStorefrontOrderStatusCommand $cmd): StorefrontOrder {
                    $order = $this->repository->findById($cmd->id, $cmd->tenantId);
                    if ($order === null) {
                        throw new \DomainException("Storefront order with ID {$cmd->id} not found.");
                    }

                    StorefrontOrderStatus::from($cmd->status);

                    return $this->repository->save(new StorefrontOrder(
                        id: $order->id,
                        tenantId: $order->tenantId,
                        userId: $order->userId,
                        reference: $order->reference,
                        status: $cmd->status,
                        currency: $order->currency,
                        subtotal: $order->subtotal,
                        taxAmount: $order->taxAmount,
                        shippingAmount: $order->shippingAmount,
                        discountAmount: $order->discountAmount,
                        totalAmount: $order->totalAmount,
                        billingName: $order->billingName,
                        billingEmail: $order->billingEmail,
                        billingPhone: $order->billingPhone,
                        shippingAddress: $order->shippingAddress,
                        notes: $order->notes,
                        cartToken: $order->cartToken,
                        createdAt: $order->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
