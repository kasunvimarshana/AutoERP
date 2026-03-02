<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Entities\StorefrontCartItem;
use Modules\Ecommerce\Infrastructure\Models\StorefrontCartItemModel;
use Modules\Ecommerce\Infrastructure\Models\StorefrontCartModel;

class StorefrontCartRepository extends BaseRepository implements StorefrontCartRepositoryInterface
{
    protected function model(): string
    {
        return StorefrontCartModel::class;
    }

    public function save(StorefrontCart $cart): StorefrontCart
    {
        if ($cart->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $cart->tenantId)
                ->findOrFail($cart->id);
        } else {
            $model = new StorefrontCartModel;
            $model->tenant_id = $cart->tenantId;
            $model->token = $cart->token;
        }

        $model->user_id = $cart->userId;
        $model->status = $cart->status;
        $model->currency = $cart->currency;
        $model->subtotal = $cart->subtotal;
        $model->tax_amount = $cart->taxAmount;
        $model->total_amount = $cart->totalAmount;
        $model->save();

        return $this->toEntity($model);
    }

    public function findById(int $id, int $tenantId): ?StorefrontCart
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByToken(string $token, int $tenantId): ?StorefrontCart
    {
        $model = StorefrontCartModel::where('token', $token)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function saveItem(StorefrontCartItem $item): StorefrontCartItem
    {
        if ($item->id !== null) {
            $model = StorefrontCartItemModel::where('tenant_id', $item->tenantId)
                ->findOrFail($item->id);
        } else {
            $model = new StorefrontCartItemModel;
            $model->tenant_id = $item->tenantId;
            $model->cart_id = $item->cartId;
        }

        $model->product_id = $item->productId;
        $model->product_name = $item->productName;
        $model->sku = $item->sku;
        $model->quantity = $item->quantity;
        $model->unit_price = $item->unitPrice;
        $model->line_total = $item->lineTotal;
        $model->save();

        return $this->toItemEntity($model);
    }

    public function findItems(int $cartId, int $tenantId): array
    {
        return StorefrontCartItemModel::where('cart_id', $cartId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (StorefrontCartItemModel $m) => $this->toItemEntity($m))
            ->all();
    }

    public function deleteItem(int $itemId, int $tenantId): void
    {
        StorefrontCartItemModel::where('tenant_id', $tenantId)
            ->findOrFail($itemId)
            ->delete();
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toEntity(StorefrontCartModel $model): StorefrontCart
    {
        return new StorefrontCart(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            token: $model->token,
            status: $model->status,
            currency: $model->currency,
            subtotal: bcadd((string) $model->subtotal, '0', 4),
            taxAmount: bcadd((string) $model->tax_amount, '0', 4),
            totalAmount: bcadd((string) $model->total_amount, '0', 4),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toItemEntity(StorefrontCartItemModel $model): StorefrontCartItem
    {
        return new StorefrontCartItem(
            id: $model->id,
            tenantId: $model->tenant_id,
            cartId: $model->cart_id,
            productId: $model->product_id,
            productName: $model->product_name,
            sku: $model->sku,
            quantity: bcadd((string) $model->quantity, '0', 4),
            unitPrice: bcadd((string) $model->unit_price, '0', 4),
            lineTotal: bcadd((string) $model->line_total, '0', 4),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
