<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\EmployeeSalaryAssignmentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeSalaryAssignmentModel;

final class EloquentEmployeeSalaryAssignmentRepository extends EloquentRepository implements EmployeeSalaryAssignmentRepositoryInterface
{
    public function __construct(EmployeeSalaryAssignmentModel $model)
    {
        parent::__construct($model);
    }
}