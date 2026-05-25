<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerVehicleModel;

final class EloquentCustomerVehicleRepository extends EloquentRepository implements CustomerVehicleRepositoryInterface
{
    public function __construct(CustomerVehicleModel $model)
    {
        parent::__construct($model);
    }
}