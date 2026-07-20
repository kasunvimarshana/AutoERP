<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class RentalDateTimeRules
{
    private const EXPLICIT_TIMEZONE_PATTERN = '/(?:Z|[+-]\d{2}:\d{2})$/';

    /** @return list<string> */
    public static function required(): array
    {
        return ['required', 'date', 'regex:'.self::EXPLICIT_TIMEZONE_PATTERN];
    }

    /** @return list<string> */
    public static function nullable(): array
    {
        return ['nullable', 'date', 'regex:'.self::EXPLICIT_TIMEZONE_PATTERN];
    }
}
