<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\BudgetLineRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetLineModel;

final class EloquentBudgetLineRepository extends FinanceRepository implements BudgetLineRepositoryInterface
{
    public function __construct(BudgetLineModel $model)
    {
        parent::__construct($model);
    }
}
