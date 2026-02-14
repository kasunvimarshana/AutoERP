<?php

namespace App\Modules\Order\Repositories;

use App\Base\BaseRepository;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;

class OrderItemRepository extends BaseRepository
{
    public function __construct(OrderItem $model)
    {
        parent::__construct($model);
    }

    public function findByOrder(string $orderId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('order_id', $orderId)
            ->get();
    }

    public function findByProduct(string $productVariantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_variant_id', $productVariantId)
            ->get();
    }
}
