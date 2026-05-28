<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobPaymentLinkRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobPaymentLinkModel;

final class EloquentVehicleServiceJobPaymentLinkRepository extends EloquentRepository implements VehicleServiceJobPaymentLinkRepositoryInterface
{
    public function __construct(VehicleServiceJobPaymentLinkModel $model)
    {
        parent::__construct($model);
    }
}
