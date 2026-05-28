<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalPaymentLinkRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalPaymentLinkModel;

final class EloquentVehicleRentalPaymentLinkRepository extends EloquentRepository implements VehicleRentalPaymentLinkRepositoryInterface
{
    public function __construct(VehicleRentalPaymentLinkModel $model)
    {
        parent::__construct($model);
    }
}
