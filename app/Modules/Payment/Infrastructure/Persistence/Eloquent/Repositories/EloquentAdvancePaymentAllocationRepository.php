<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentAllocationModel;

final class EloquentAdvancePaymentAllocationRepository extends EloquentRepository implements AdvancePaymentAllocationRepositoryInterface
{
    public function __construct(AdvancePaymentAllocationModel $model)
    {
        parent::__construct($model);
    }
}