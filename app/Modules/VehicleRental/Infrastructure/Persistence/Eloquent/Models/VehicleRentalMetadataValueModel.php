<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalMetadataValueModel extends CoreModel
{
    protected $table = 'vehicle_rental_metadata_values';

    protected $guarded = ['id'];
}
