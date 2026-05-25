<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\BudgetRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetModel;

final class EloquentBudgetRepository extends FinanceRepository implements BudgetRepositoryInterface
{
    public function __construct(BudgetModel $model)
    {
        parent::__construct($model);
    }
}
