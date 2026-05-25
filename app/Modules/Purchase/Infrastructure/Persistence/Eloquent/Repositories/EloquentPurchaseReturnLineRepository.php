<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;

final class EloquentPurchaseReturnLineRepository extends EloquentRepository implements PurchaseReturnLineRepositoryInterface
{
    public function __construct(PurchaseReturnLineModel $model)
    {
        parent::__construct($model);
    }
}