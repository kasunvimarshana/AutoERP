<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

final class EloquentPurchaseOrderRepository extends EloquentRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrderModel $model)
    {
        parent::__construct($model);
    }
}