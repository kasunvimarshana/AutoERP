<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRateRuleRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalRateRuleModel;

final class EloquentVehicleRentalRateRuleRepository extends EloquentRepository implements VehicleRentalRateRuleRepositoryInterface
{
    public function __construct(VehicleRentalRateRuleModel $model)
    {
        parent::__construct($model);
    }
}
