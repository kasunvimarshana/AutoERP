<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\AddCartItemCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Entities\StorefrontCartItem;
use Modules\Ecommerce\Domain\Enums\StorefrontCartStatus;

class AddCartItemHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontCartRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(AddCartItemCommand $command): StorefrontCartItem
    {
        return $this->transaction(function () use ($command): StorefrontCartItem {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (AddCartItemCommand $cmd): StorefrontCartItem {
                    $cart = $this->repository->findByToken($cmd->cartToken, $cmd->tenantId);
                    if ($cart === null) {
                        throw new \DomainException("Cart with token '{$cmd->cartToken}' not found.");
                    }
                    if ($cart->status !== StorefrontCartStatus::Active->value) {
                        throw new \DomainException('Cannot add items to a non-active cart.');
                    }

                    $lineTotal = bcmul($cmd->quantity, $cmd->unitPrice, 4);

                    $item = $this->repository->saveItem(new StorefrontCartItem(
                        id: null,
                        tenantId: $cmd->tenantId,
                        cartId: $cart->id,
                        productId: $cmd->productId,
                        productName: $cmd->productName,
                        sku: $cmd->sku,
                        quantity: $cmd->quantity,
                        unitPrice: $cmd->unitPrice,
                        lineTotal: $lineTotal,
                        createdAt: null,
                        updatedAt: null,
                    ));

                    $this->recalculateCart($cart, $cmd->tenantId);

                    return $item;
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
