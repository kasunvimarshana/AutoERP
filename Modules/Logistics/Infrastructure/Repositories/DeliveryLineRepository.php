<?php

namespace Modules\Logistics\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Logistics\Domain\Contracts\DeliveryLineRepositoryInterface;
use Modules\Logistics\Infrastructure\Models\DeliveryLineModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class DeliveryLineRepository extends BaseEloquentRepository implements DeliveryLineRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new DeliveryLineModel());
    }

    public function findByDeliveryOrder(string $deliveryOrderId): Collection
    {
        return DeliveryLineModel::where('delivery_order_id', $deliveryOrderId)->get();
    }
}
