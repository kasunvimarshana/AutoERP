<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\EmployeeContactRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContactModel;

final class EloquentEmployeeContactRepository extends EloquentRepository implements EmployeeContactRepositoryInterface
{
    public function __construct(EmployeeContactModel $model)
    {
        parent::__construct($model);
    }
}