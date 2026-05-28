<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalRateRuleModel extends CoreModel
{
    protected $table = 'vehicle_rental_rate_rules';

    protected $guarded = ['id'];
}
