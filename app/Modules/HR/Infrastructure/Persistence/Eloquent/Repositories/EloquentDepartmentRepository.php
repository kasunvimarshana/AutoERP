<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\DepartmentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;

final class EloquentDepartmentRepository extends EloquentRepository implements DepartmentRepositoryInterface
{
    public function __construct(DepartmentModel $model)
    {
        parent::__construct($model);
    }
}