<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\ArTransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;

final class EloquentArTransactionRepository extends FinanceRepository implements ArTransactionRepositoryInterface
{
    public function __construct(ArTransactionModel $model)
    {
        parent::__construct($model);
    }
}
