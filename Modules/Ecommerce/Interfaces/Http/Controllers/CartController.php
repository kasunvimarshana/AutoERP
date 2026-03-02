<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Application\Commands\AddCartItemCommand;
use Modules\Ecommerce\Application\Commands\CheckoutCartCommand;
use Modules\Ecommerce\Application\Commands\CreateCartCommand;
use Modules\Ecommerce\Application\Commands\RemoveCartItemCommand;
use Modules\Ecommerce\Application\Services\CartService;
use Modules\Ecommerce\Interfaces\Http\Requests\AddCartItemRequest;
use Modules\Ecommerce\Interfaces\Http\Requests\CheckoutCartRequest;
use Modules\Ecommerce\Interfaces\Http\Requests\CreateCartRequest;
use Modules\Ecommerce\Interfaces\Http\Resources\StorefrontCartResource;
use Modules\Ecommerce\Interfaces\Http\Resources\StorefrontOrderResource;

class CartController extends BaseController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function store(CreateCartRequest $request): JsonResponse
    {
        try {
            $cart = $this->service->create(new CreateCartCommand(
                tenantId: $request->validated('tenant_id'),
                userId: $request->validated('user_id'),
                currency: $request->validated('currency'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new StorefrontCartResource($cart))->resolve(),
            message: 'Cart created successfully',
            status: 201,
        );
    }

    public function show(string $token): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $cart = $this->service->findByToken($token, $tenantId);

        if ($cart === null) {
            return $this->error('Cart not found', status: 404);
        }

        $items = $this->service->findItems($cart->id, $tenantId);

        return $this->success(
            data: [
                'cart' => (new StorefrontCartResource($cart))->resolve(),
                'items' => array_map(
                    fn ($item) => (new \Modules\Ecommerce\Interfaces\Http\Resources\StorefrontCartItemResource($item))->resolve(),
                    $items
                ),
            ],
            message: 'Cart retrieved successfully',
        );
    }

    public function addItem(AddCartItemRequest $request, string $token): JsonResponse
    {
        try {
            $item = $this->service->addItem(new AddCartItemCommand(
                tenantId: $request->validated('tenant_id'),
                cartToken: $token,
                productId: $request->validated('product_id'),
                productName: $request->validated('product_name'),
                sku: $request->validated('sku'),
                quantity: (string) $request->validated('quantity'),
                unitPrice: (string) $request->validated('unit_price'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new \Modules\Ecommerce\Interfaces\Http\Resources\StorefrontCartItemResource($item))->resolve(),
            message: 'Item added to cart successfully',
            status: 201,
        );
    }

    public function removeItem(string $token, int $itemId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->removeItem(new RemoveCartItemCommand(
                tenantId: $tenantId,
                cartToken: $token,
                itemId: $itemId,
            ));

            return $this->success(message: 'Item removed from cart successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function checkout(CheckoutCartRequest $request, string $token): JsonResponse
    {
        try {
            $order = $this->service->checkout(new CheckoutCartCommand(
                tenantId: $request->validated('tenant_id'),
                cartToken: $token,
                userId: $request->validated('user_id'),
                billingName: $request->validated('billing_name'),
                billingEmail: $request->validated('billing_email'),
                billingPhone: $request->validated('billing_phone'),
                shippingAddress: $request->validated('shipping_address'),
                shippingAmount: (string) $request->validated('shipping_amount'),
                discountAmount: (string) $request->validated('discount_amount'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new StorefrontOrderResource($order))->resolve(),
            message: 'Checkout completed successfully',
            status: 201,
        );
    }
}
