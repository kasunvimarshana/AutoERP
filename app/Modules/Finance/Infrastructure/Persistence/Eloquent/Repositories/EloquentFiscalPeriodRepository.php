<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalPeriodModel;

final class EloquentFiscalPeriodRepository extends FinanceRepository implements FiscalPeriodRepositoryInterface
{
    public function __construct(FiscalPeriodModel $model)
    {
        parent::__construct($model);
    }
}
