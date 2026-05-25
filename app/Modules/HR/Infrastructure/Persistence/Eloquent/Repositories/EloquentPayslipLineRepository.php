<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\PayslipLineRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipLineModel;

final class EloquentPayslipLineRepository extends EloquentRepository implements PayslipLineRepositoryInterface
{
    public function __construct(PayslipLineModel $model)
    {
        parent::__construct($model);
    }
}