<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Contracts;

use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Entities\StorefrontCartItem;

interface StorefrontCartRepositoryInterface
{
    public function save(StorefrontCart $cart): StorefrontCart;

    public function findById(int $id, int $tenantId): ?StorefrontCart;

    public function findByToken(string $token, int $tenantId): ?StorefrontCart;

    public function saveItem(StorefrontCartItem $item): StorefrontCartItem;

    public function findItems(int $cartId, int $tenantId): array;

    public function deleteItem(int $itemId, int $tenantId): void;

    public function delete(int $id, int $tenantId): void;
}
