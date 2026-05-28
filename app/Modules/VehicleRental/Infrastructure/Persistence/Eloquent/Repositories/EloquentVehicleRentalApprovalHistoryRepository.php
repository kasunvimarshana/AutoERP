<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalApprovalHistoryRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalApprovalHistoryModel;

final class EloquentVehicleRentalApprovalHistoryRepository extends EloquentRepository implements VehicleRentalApprovalHistoryRepositoryInterface
{
    public function __construct(VehicleRentalApprovalHistoryModel $model)
    {
        parent::__construct($model);
    }
}
