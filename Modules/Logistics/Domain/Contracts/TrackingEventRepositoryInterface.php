<?php

namespace Modules\Logistics\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface TrackingEventRepositoryInterface extends RepositoryInterface
{
    public function findByDeliveryOrder(string $deliveryOrderId): Collection;
}
