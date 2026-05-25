<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\HolidayRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\HolidayModel;

final class EloquentHolidayRepository extends EloquentRepository implements HolidayRepositoryInterface
{
    public function __construct(HolidayModel $model)
    {
        parent::__construct($model);
    }
}