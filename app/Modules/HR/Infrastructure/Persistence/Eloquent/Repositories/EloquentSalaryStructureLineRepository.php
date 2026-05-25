<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\SalaryStructureLineRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureLineModel;

final class EloquentSalaryStructureLineRepository extends EloquentRepository implements SalaryStructureLineRepositoryInterface
{
    public function __construct(SalaryStructureLineModel $model)
    {
        parent::__construct($model);
    }
}