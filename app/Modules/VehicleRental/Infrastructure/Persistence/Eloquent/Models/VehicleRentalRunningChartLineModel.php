<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalRunningChartLineModel extends CoreModel
{
    protected $table = 'vehicle_rental_running_chart_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'running_chart_id' => 'integer',
            'rental_vehicle_id' => 'integer',
        ]);
    }
}
