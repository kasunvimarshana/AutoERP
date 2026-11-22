<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Models;

final class CustomerAgreementHistory extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_customer_agreements_history';
}
