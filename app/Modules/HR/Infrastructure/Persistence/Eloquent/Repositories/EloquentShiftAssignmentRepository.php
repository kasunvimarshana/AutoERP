<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\ShiftAssignmentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftAssignmentModel;

final class EloquentShiftAssignmentRepository extends EloquentRepository implements ShiftAssignmentRepositoryInterface
{
    public function __construct(ShiftAssignmentModel $model)
    {
        parent::__construct($model);
    }
}