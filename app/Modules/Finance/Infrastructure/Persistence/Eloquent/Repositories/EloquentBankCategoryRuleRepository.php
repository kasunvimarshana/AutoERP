<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\BankCategoryRuleRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankCategoryRuleModel;

final class EloquentBankCategoryRuleRepository extends FinanceRepository implements BankCategoryRuleRepositoryInterface
{
    public function __construct(BankCategoryRuleModel $model)
    {
        parent::__construct($model);
    }
}
