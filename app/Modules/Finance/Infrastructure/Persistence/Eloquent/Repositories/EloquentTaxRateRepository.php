<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\TaxRateRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;

final class EloquentTaxRateRepository extends FinanceRepository implements TaxRateRepositoryInterface
{
    public function __construct(TaxRateModel $model)
    {
        parent::__construct($model);
    }
}
