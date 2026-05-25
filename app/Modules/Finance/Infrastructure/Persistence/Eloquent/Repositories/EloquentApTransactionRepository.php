<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\ApTransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;

final class EloquentApTransactionRepository extends FinanceRepository implements ApTransactionRepositoryInterface
{
    public function __construct(ApTransactionModel $model)
    {
        parent::__construct($model);
    }
}
