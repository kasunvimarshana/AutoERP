<?php

declare(strict_types=1);

namespace Modules\Purchase\Policies;

use Modules\Auth\Models\User;
use Modules\Purchase\Enums\GoodsReceiptStatus;
use Modules\Purchase\Models\GoodsReceipt;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('goods_receipts.view');
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->hasPermission('goods_receipts.view')
            && $user->tenant_id === $goodsReceipt->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('goods_receipts.create');
    }

    public function update(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->hasPermission('goods_receipts.update')
            && $user->tenant_id === $goodsReceipt->tenant_id
            && $goodsReceipt->status === GoodsReceiptStatus::DRAFT;
    }

    public function delete(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->hasPermission('goods_receipts.delete')
            && $user->tenant_id === $goodsReceipt->tenant_id
            && $goodsReceipt->status === GoodsReceiptStatus::DRAFT;
    }

    public function confirm(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->hasPermission('goods_receipts.confirm')
            && $user->tenant_id === $goodsReceipt->tenant_id
            && $goodsReceipt->status === GoodsReceiptStatus::DRAFT;
    }

    public function postToInventory(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->hasPermission('goods_receipts.post_inventory')
            && $user->tenant_id === $goodsReceipt->tenant_id
            && $goodsReceipt->status === GoodsReceiptStatus::CONFIRMED;
    }

    public function cancel(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->hasPermission('goods_receipts.cancel')
            && $user->tenant_id === $goodsReceipt->tenant_id
            && in_array($goodsReceipt->status, [GoodsReceiptStatus::DRAFT, GoodsReceiptStatus::CONFIRMED]);
    }
}
