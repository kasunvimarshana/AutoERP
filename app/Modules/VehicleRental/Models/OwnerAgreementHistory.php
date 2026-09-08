<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class OwnerAgreementHistory extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_owner_agreements_history';
}
