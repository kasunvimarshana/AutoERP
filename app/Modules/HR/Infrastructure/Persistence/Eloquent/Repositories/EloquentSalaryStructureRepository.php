<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\SalaryStructureRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureModel;

final class EloquentSalaryStructureRepository extends EloquentRepository implements SalaryStructureRepositoryInterface
{
    public function __construct(SalaryStructureModel $model)
    {
        parent::__construct($model);
    }
}