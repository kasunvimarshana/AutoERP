<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobDocumentLinkRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobDocumentLinkModel;

final class EloquentVehicleServiceJobDocumentLinkRepository extends EloquentRepository implements VehicleServiceJobDocumentLinkRepositoryInterface
{
    public function __construct(VehicleServiceJobDocumentLinkModel $model)
    {
        parent::__construct($model);
    }
}
