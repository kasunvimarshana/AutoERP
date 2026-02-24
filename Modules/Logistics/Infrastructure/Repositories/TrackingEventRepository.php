<?php

namespace Modules\Logistics\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;
use Modules\Logistics\Infrastructure\Models\TrackingEventModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class TrackingEventRepository extends BaseEloquentRepository implements TrackingEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TrackingEventModel());
    }

    public function findByDeliveryOrder(string $deliveryOrderId): Collection
    {
        return TrackingEventModel::where('delivery_order_id', $deliveryOrderId)
            ->orderBy('occurred_at', 'asc')
            ->get();
    }
}
