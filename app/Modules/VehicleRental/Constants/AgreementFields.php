<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class AgreementFields
{
    public const DATE_FORMAT = 'Y-m-d';

    public const INITIAL_VERSION = 1;

    public const REFERENCE_LENGTH = 100;

    public const NOTES_LENGTH = 5000;

    public const DECIMAL_SCALE = 6;

    public const DECIMAL_PRECISION = 20;

    public const ZERO = '0';

    public const DECIMAL_PATTERN = '/^(?:0|[1-9]\d{0,13})(?:\.\d{1,6})?$/D';

    public const AMOUNTS = ['base_rate', 'included_km', 'excess_km_rate', 'non_ac_rate', 'front_ac_rate', 'dual_ac_rate', 'driver_rate', 'normal_ot_rate', 'double_ot_rate', 'triple_ot_rate', 'night_out_rate', 'deposit_requirement'];

    public const MUTABLE = ['reference', 'party_id', 'vehicle_id', 'agreed_on', 'executing_on', 'starts_on', 'ends_on', 'basis', 'driver_mode', 'currency_id', 'terms', 'notes'];

    private function __construct() {}
}
