<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\ShiftRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftModel;

final class EloquentShiftRepository extends EloquentRepository implements ShiftRepositoryInterface
{
    public function __construct(ShiftModel $model)
    {
        parent::__construct($model);
    }
}