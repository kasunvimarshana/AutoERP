<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticModel;

final class EloquentVehicleServiceDiagnosticRepository extends EloquentRepository implements VehicleServiceDiagnosticRepositoryInterface
{
    public function __construct(VehicleServiceDiagnosticModel $model)
    {
        parent::__construct($model);
    }
}