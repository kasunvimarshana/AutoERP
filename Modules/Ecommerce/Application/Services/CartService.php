<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Services;

use Modules\Ecommerce\Application\Commands\AddCartItemCommand;
use Modules\Ecommerce\Application\Commands\CheckoutCartCommand;
use Modules\Ecommerce\Application\Commands\CreateCartCommand;
use Modules\Ecommerce\Application\Commands\RemoveCartItemCommand;
use Modules\Ecommerce\Application\Handlers\AddCartItemHandler;
use Modules\Ecommerce\Application\Handlers\CheckoutCartHandler;
use Modules\Ecommerce\Application\Handlers\CreateCartHandler;
use Modules\Ecommerce\Application\Handlers\RemoveCartItemHandler;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Entities\StorefrontCartItem;
use Modules\Ecommerce\Domain\Entities\StorefrontOrder;

class CartService
{
    public function __construct(
        private readonly StorefrontCartRepositoryInterface $repository,
        private readonly CreateCartHandler $createHandler,
        private readonly AddCartItemHandler $addItemHandler,
        private readonly RemoveCartItemHandler $removeItemHandler,
        private readonly CheckoutCartHandler $checkoutHandler,
    ) {}

    public function create(CreateCartCommand $cmd): StorefrontCart
    {
        return $this->createHandler->handle($cmd);
    }

    public function findByToken(string $token, int $tenantId): ?StorefrontCart
    {
        return $this->repository->findByToken($token, $tenantId);
    }

    public function addItem(AddCartItemCommand $cmd): StorefrontCartItem
    {
        return $this->addItemHandler->handle($cmd);
    }

    public function removeItem(RemoveCartItemCommand $cmd): void
    {
        $this->removeItemHandler->handle($cmd);
    }

    public function checkout(CheckoutCartCommand $cmd): StorefrontOrder
    {
        return $this->checkoutHandler->handle($cmd);
    }

    public function findItems(int $cartId, int $tenantId): array
    {
        return $this->repository->findItems($cartId, $tenantId);
    }
}
