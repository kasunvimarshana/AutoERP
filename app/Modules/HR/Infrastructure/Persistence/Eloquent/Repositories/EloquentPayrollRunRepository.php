<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\PayrollRunRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;

final class EloquentPayrollRunRepository extends EloquentRepository implements PayrollRunRepositoryInterface
{
    public function __construct(PayrollRunModel $model)
    {
        parent::__construct($model);
    }
}