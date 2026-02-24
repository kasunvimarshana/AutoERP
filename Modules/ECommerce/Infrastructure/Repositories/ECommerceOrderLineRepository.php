<?php

namespace Modules\ECommerce\Infrastructure\Repositories;

use Modules\ECommerce\Domain\Contracts\ECommerceOrderLineRepositoryInterface;
use Modules\ECommerce\Infrastructure\Models\ECommerceOrderLineModel;

class ECommerceOrderLineRepository implements ECommerceOrderLineRepositoryInterface
{
    public function findByOrder(string $orderId): iterable
    {
        return ECommerceOrderLineModel::where('order_id', $orderId)->get();
    }

    public function create(array $data): object
    {
        return ECommerceOrderLineModel::create($data);
    }

    public function deleteByOrder(string $orderId): void
    {
        ECommerceOrderLineModel::where('order_id', $orderId)->delete();
    }
}
