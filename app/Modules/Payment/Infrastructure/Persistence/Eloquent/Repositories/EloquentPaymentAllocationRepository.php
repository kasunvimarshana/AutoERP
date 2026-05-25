<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentAllocationModel;

final class EloquentPaymentAllocationRepository extends EloquentRepository implements PaymentAllocationRepositoryInterface
{
    public function __construct(PaymentAllocationModel $model)
    {
        parent::__construct($model);
    }
}