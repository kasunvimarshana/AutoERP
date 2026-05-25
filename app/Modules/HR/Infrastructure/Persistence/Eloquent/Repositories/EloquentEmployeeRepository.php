<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\EmployeeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;

final class EloquentEmployeeRepository extends EloquentRepository implements EmployeeRepositoryInterface
{
    public function __construct(EmployeeModel $model)
    {
        parent::__construct($model);
    }
}