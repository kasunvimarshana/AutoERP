<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;

final class EloquentAccountRepository extends FinanceRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
    }
}
