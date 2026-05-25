<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\CostCenterRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\CostCenterModel;

final class EloquentCostCenterRepository extends FinanceRepository implements CostCenterRepositoryInterface
{
    public function __construct(CostCenterModel $model)
    {
        parent::__construct($model);
    }
}
