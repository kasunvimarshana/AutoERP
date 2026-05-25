<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;

final class EloquentPurchaseOrderLineRepository extends EloquentRepository implements PurchaseOrderLineRepositoryInterface
{
    public function __construct(PurchaseOrderLineModel $model)
    {
        parent::__construct($model);
    }
}