<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class VehicleRentalSource
{
    public const AGREEMENT_DOCUMENT = 'vehicle_rental_agreement';
    public const RUNNING_CHART_DOCUMENT = 'vehicle_rental_running_chart';
    public const CALCULATION_DOCUMENT = 'vehicle_rental_calculation';

    private function __construct() {}
}
