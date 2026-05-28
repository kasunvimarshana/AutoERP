<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalReplacementModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_rental_replacements';

    protected $guarded = ['id'];
}
