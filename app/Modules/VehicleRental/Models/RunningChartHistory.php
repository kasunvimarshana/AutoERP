<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class RunningChartHistory extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_running_chart_history';
}
