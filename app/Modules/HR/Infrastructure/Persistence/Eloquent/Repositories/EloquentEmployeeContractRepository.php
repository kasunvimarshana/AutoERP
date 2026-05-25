<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\EmployeeContractRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContractModel;

final class EloquentEmployeeContractRepository extends EloquentRepository implements EmployeeContractRepositoryInterface
{
    public function __construct(EmployeeContractModel $model)
    {
        parent::__construct($model);
    }
}