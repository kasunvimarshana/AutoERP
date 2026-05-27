<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\FinanceProcessedEventRepositoryInterface as ProcessedEventRepo;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FinanceProcessedEventModel;

final class EloquentFinanceProcessedEventRepository extends FinanceRepository implements ProcessedEventRepo
{
    public function __construct(FinanceProcessedEventModel $model)
    {
        parent::__construct($model);
    }
}
