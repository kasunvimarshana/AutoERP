<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\TaxRuleRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;

final class EloquentTaxRuleRepository extends FinanceRepository implements TaxRuleRepositoryInterface
{
    public function __construct(TaxRuleModel $model)
    {
        parent::__construct($model);
    }
}
