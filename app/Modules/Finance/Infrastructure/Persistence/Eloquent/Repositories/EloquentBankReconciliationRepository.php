<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\BankReconciliationRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankReconciliationModel;

final class EloquentBankReconciliationRepository extends FinanceRepository implements BankReconciliationRepositoryInterface
{
    public function __construct(BankReconciliationModel $model)
    {
        parent::__construct($model);
    }
}
