<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalSettingModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_rental_settings';

    protected $guarded = ['id'];
}
