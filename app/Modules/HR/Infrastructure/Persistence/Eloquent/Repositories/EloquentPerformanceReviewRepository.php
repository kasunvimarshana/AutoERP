<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\PerformanceReviewRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;

final class EloquentPerformanceReviewRepository extends EloquentRepository implements PerformanceReviewRepositoryInterface
{
    public function __construct(PerformanceReviewModel $model)
    {
        parent::__construct($model);
    }
}