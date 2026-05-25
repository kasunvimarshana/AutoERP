<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\EmploymentTypeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmploymentTypeModel;

final class EloquentEmploymentTypeRepository extends EloquentRepository implements EmploymentTypeRepositoryInterface
{
    public function __construct(EmploymentTypeModel $model)
    {
        parent::__construct($model);
    }
}