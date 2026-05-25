<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;

final class EloquentGrnLineRepository extends EloquentRepository implements GrnLineRepositoryInterface
{
    public function __construct(GrnLineModel $model)
    {
        parent::__construct($model);
    }
}