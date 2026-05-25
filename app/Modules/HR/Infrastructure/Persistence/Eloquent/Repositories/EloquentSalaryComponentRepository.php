<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\SalaryComponentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryComponentModel;

final class EloquentSalaryComponentRepository extends EloquentRepository implements SalaryComponentRepositoryInterface
{
    public function __construct(SalaryComponentModel $model)
    {
        parent::__construct($model);
    }
}