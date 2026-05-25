<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticLineRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticLineModel;

final class EloquentVehicleServiceDiagnosticLineRepository extends EloquentRepository implements VehicleServiceDiagnosticLineRepositoryInterface
{
    public function __construct(VehicleServiceDiagnosticLineModel $model)
    {
        parent::__construct($model);
    }
}