<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;

final class EloquentPurchaseReturnRepository extends EloquentRepository implements PurchaseReturnRepositoryInterface
{
    public function __construct(PurchaseReturnModel $model)
    {
        parent::__construct($model);
    }
}