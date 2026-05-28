<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchasePaymentAllocationRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchasePaymentAllocationModel;

final class EloquentPurchasePaymentAllocationRepository extends EloquentRepository implements PurchasePaymentAllocationRepositoryInterface
{
    public function __construct(PurchasePaymentAllocationModel $model)
    {
        parent::__construct($model);
    }
}
