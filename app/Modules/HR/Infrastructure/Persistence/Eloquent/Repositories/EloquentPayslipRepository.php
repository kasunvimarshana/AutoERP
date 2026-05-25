<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\PayslipRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;

final class EloquentPayslipRepository extends EloquentRepository implements PayslipRepositoryInterface
{
    public function __construct(PayslipModel $model)
    {
        parent::__construct($model);
    }
}