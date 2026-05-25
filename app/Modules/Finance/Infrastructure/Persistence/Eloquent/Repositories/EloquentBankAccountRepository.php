<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\BankAccountRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;

final class EloquentBankAccountRepository extends FinanceRepository implements BankAccountRepositoryInterface
{
    public function __construct(BankAccountModel $model)
    {
        parent::__construct($model);
    }
}
