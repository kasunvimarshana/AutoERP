<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalAgreementLineModel extends CoreModel
{
    protected $table = 'vehicle_rental_agreement_lines';

    protected $guarded = ['id'];
}
