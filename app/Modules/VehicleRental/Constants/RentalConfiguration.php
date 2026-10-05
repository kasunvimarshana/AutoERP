<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

use Modules\Configuration\Constants\ConfigurationKey;

final class RentalConfiguration
{
    public const WORKSPACE_TIMEZONE = ConfigurationKey::WORKSPACE_TIMEZONE;

    private function __construct() {}
}
