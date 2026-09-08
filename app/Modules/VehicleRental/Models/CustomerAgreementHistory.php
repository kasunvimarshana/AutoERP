<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class CustomerAgreementHistory extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_customer_agreements_history';
}
