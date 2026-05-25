<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\TaxGroupRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;

final class EloquentTaxGroupRepository extends FinanceRepository implements TaxGroupRepositoryInterface
{
    public function __construct(TaxGroupModel $model)
    {
        parent::__construct($model);
    }
}
