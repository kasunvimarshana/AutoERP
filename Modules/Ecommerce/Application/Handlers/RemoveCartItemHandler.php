<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\RemoveCartItemCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Enums\StorefrontCartStatus;

class RemoveCartItemHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontCartRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(RemoveCartItemCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (RemoveCartItemCommand $cmd): void {
                    $cart = $this->repository->findByToken($cmd->cartToken, $cmd->tenantId);
                    if ($cart === null) {
                        throw new \DomainException("Cart with token '{$cmd->cartToken}' not found.");
                    }
                    if ($cart->status !== StorefrontCartStatus::Active->value) {
                        throw new \DomainException('Cannot remove items from a non-active cart.');
                    }

                    $this->repository->deleteItem($cmd->itemId, $cmd->tenantId);

                    $this->recalculateCart($cart, $cmd->tenantId);
                });
        });
    }

    private function recalculateCart(StorefrontCart $cart, int $tenantId): void
    {
        $allItems = $this->repository->findItems($cart->id, $tenantId);
        $subtotal = '0';
        foreach ($allItems as $item) {
            $subtotal = bcadd($subtotal, $item->lineTotal, 4);
        }

        $this->repository->save(new StorefrontCart(
            id: $cart->id,
            tenantId: $cart->tenantId,
            userId: $cart->userId,
            token: $cart->token,
            status: $cart->status,
            currency: $cart->currency,
            subtotal: $subtotal,
            taxAmount: $cart->taxAmount,
            totalAmount: $subtotal,
            createdAt: $cart->createdAt,
            updatedAt: null,
        ));
    }
}
