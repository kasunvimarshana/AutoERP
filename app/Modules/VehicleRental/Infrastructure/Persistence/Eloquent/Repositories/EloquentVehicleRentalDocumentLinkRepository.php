<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalDocumentLinkRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalDocumentLinkModel;

final class EloquentVehicleRentalDocumentLinkRepository extends EloquentRepository implements VehicleRentalDocumentLinkRepositoryInterface
{
    public function __construct(VehicleRentalDocumentLinkModel $model)
    {
        parent::__construct($model);
    }
}
