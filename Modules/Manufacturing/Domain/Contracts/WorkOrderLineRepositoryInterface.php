<?php

namespace Modules\Manufacturing\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface WorkOrderLineRepositoryInterface extends RepositoryInterface
{
    public function findByWorkOrder(string $workOrderId): Collection;
}
