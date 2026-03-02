<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\CheckoutCartCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Contracts\StorefrontOrderRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Entities\StorefrontOrder;
use Modules\Ecommerce\Domain\Entities\StorefrontOrderLine;
use Modules\Ecommerce\Domain\Enums\StorefrontCartStatus;
use Modules\Ecommerce\Domain\Enums\StorefrontOrderStatus;

class CheckoutCartHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontCartRepositoryInterface $cartRepository,
        private readonly StorefrontOrderRepositoryInterface $orderRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CheckoutCartCommand $command): StorefrontOrder
    {
        return $this->transaction(function () use ($command): StorefrontOrder {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CheckoutCartCommand $cmd): StorefrontOrder {
                    $cart = $this->cartRepository->findByToken($cmd->cartToken, $cmd->tenantId);
                    if ($cart === null) {
                        throw new \DomainException("Cart with token '{$cmd->cartToken}' not found.");
                    }
                    if ($cart->status !== StorefrontCartStatus::Active->value) {
                        throw new \DomainException('Cart has already been converted or abandoned.');
                    }

                    $cartItems = $this->cartRepository->findItems($cart->id, $cmd->tenantId);
                    if (empty($cartItems)) {
                        throw new \DomainException('Cannot checkout an empty cart.');
                    }

                    $subtotal = '0';
                    foreach ($cartItems as $item) {
                        $subtotal = bcadd($subtotal, $item->lineTotal, 4);
                    }

                    $taxAmount = '0.0000';
                    $totalAmount = bcadd($subtotal, $cmd->shippingAmount, 4);
                    $totalAmount = bcsub($totalAmount, $cmd->discountAmount, 4);

                    $reference = 'ECO-ORD-'.date('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

                    $order = $this->orderRepository->save(new StorefrontOrder(
                        id: null,
                        tenantId: $cmd->tenantId,
                        userId: $cmd->userId,
                        reference: $reference,
                        status: StorefrontOrderStatus::Pending->value,
                        currency: $cart->currency,
                        subtotal: $subtotal,
                        taxAmount: $taxAmount,
                        shippingAmount: $cmd->shippingAmount,
                        discountAmount: $cmd->discountAmount,
                        totalAmount: $totalAmount,
                        billingName: $cmd->billingName,
                        billingEmail: $cmd->billingEmail,
                        billingPhone: $cmd->billingPhone,
                        shippingAddress: $cmd->shippingAddress,
                        notes: $cmd->notes,
                        cartToken: $cmd->cartToken,
                        createdAt: null,
                        updatedAt: null,
                    ));

                    $lines = array_map(
                        fn ($item) => new StorefrontOrderLine(
                            id: null,
                            tenantId: $cmd->tenantId,
                            orderId: $order->id,
                            productId: $item->productId,
                            productName: $item->productName,
                            sku: $item->sku,
                            quantity: $item->quantity,
                            unitPrice: $item->unitPrice,
                            lineTotal: $item->lineTotal,
                            createdAt: null,
                            updatedAt: null,
                        ),
                        $cartItems
                    );
                    $this->orderRepository->saveLines($order->id, $lines);

                    // Mark cart as converted
                    $this->cartRepository->save(new StorefrontCart(
                        id: $cart->id,
                        tenantId: $cart->tenantId,
                        userId: $cart->userId,
                        token: $cart->token,
                        status: StorefrontCartStatus::Converted->value,
                        currency: $cart->currency,
                        subtotal: $cart->subtotal,
                        taxAmount: $cart->taxAmount,
                        totalAmount: $cart->totalAmount,
                        createdAt: $cart->createdAt,
                        updatedAt: null,
                    ));

                    return $order;
                });
        });
    }
}
