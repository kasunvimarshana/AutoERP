<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class RunningChartFields
{
    public const MAX_COUNT = 2147483647;

    public const DRIVER_REFERENCE_LENGTH = 255;

    public const DISTANCES = ['start_odometer', 'end_odometer', 'garage_km', 'commercial_km'];

    public const COUNTS = ['normal_ot_minutes', 'double_ot_minutes', 'triple_ot_minutes', 'night_outs'];

    public const DRIVER_IDENTITY = ['driver_identity_source', 'driver_employee_id', 'driver_name_snapshot', 'driver_reference_snapshot'];

    public const MUTABLE = ['reference', 'starts_at', 'ends_at', 'start_odometer', 'end_odometer', 'garage_km', 'commercial_km', 'ac_mode', 'normal_ot_minutes', 'double_ot_minutes', 'triple_ot_minutes', 'night_outs', 'driver_identity_source', 'driver_employee_id', 'driver_name_snapshot', 'driver_reference_snapshot', 'driver_observation', 'notes'];
}
