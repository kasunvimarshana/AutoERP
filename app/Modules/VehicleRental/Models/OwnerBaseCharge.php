<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class OwnerBaseCharge extends RentalCharge
{
    protected $table = 'vehicle_rental_owner_base_charges';
}
