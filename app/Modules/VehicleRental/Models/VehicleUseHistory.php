<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class VehicleUseHistory extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_use_history';
}
