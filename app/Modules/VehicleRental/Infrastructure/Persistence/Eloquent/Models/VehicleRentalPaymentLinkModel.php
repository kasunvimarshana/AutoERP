<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalPaymentLinkModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_rental_payment_links';

    protected $guarded = ['id'];
}
