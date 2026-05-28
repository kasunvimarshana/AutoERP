<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesSettingRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesSettingModel;

final class EloquentSalesSettingRepository extends EloquentRepository implements SalesSettingRepositoryInterface
{
    public function __construct(SalesSettingModel $model)
    {
        parent::__construct($model);
    }
}
