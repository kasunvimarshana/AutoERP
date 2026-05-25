<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\LeavePolicyLineRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyLineModel;

final class EloquentLeavePolicyLineRepository extends EloquentRepository implements LeavePolicyLineRepositoryInterface
{
    public function __construct(LeavePolicyLineModel $model)
    {
        parent::__construct($model);
    }
}