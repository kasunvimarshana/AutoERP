<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseStatusHistoryRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseStatusHistoryModel;

final class EloquentPurchaseStatusHistoryRepository extends EloquentRepository implements PurchaseStatusHistoryRepositoryInterface
{
    public function __construct(PurchaseStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}
