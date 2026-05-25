<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\FiscalYearRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;

final class EloquentFiscalYearRepository extends FinanceRepository implements FiscalYearRepositoryInterface
{
    public function __construct(FiscalYearModel $model)
    {
        parent::__construct($model);
    }
}
