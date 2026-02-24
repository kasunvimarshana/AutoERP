<?php

namespace Modules\Manufacturing\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Manufacturing\Domain\Contracts\WorkOrderLineRepositoryInterface;
use Modules\Manufacturing\Infrastructure\Models\WorkOrderLineModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class WorkOrderLineRepository extends BaseEloquentRepository implements WorkOrderLineRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new WorkOrderLineModel());
    }

    public function findByWorkOrder(string $workOrderId): Collection
    {
        return WorkOrderLineModel::where('work_order_id', $workOrderId)->get();
    }
}
