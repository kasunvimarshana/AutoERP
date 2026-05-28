<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesPaymentAllocationRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesPaymentAllocationModel;

final class EloquentSalesPaymentAllocationRepository extends EloquentRepository implements SalesPaymentAllocationRepositoryInterface
{
    public function __construct(SalesPaymentAllocationModel $model)
    {
        parent::__construct($model);
    }
}
