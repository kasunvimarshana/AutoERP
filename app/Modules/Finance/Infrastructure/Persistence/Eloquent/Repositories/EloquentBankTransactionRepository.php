<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\BankTransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;

final class EloquentBankTransactionRepository extends FinanceRepository implements BankTransactionRepositoryInterface
{
    public function __construct(BankTransactionModel $model)
    {
        parent::__construct($model);
    }
}
