<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Modules\VehicleRental\Constants\OperationalFields;

final class OperationalTime
{
    public static function parse(mixed $value, string $field): CarbonImmutable
    {
        Validator::make([$field => $value], [$field => ['required', 'string', 'date_format:'.OperationalFields::TIMESTAMP_FORMAT]])->validate();

        return CarbonImmutable::createFromFormat(OperationalFields::TIMESTAMP_FORMAT, $value);
    }

    public static function database(CarbonImmutable $value): string
    {
        return $value->setTimezone(OperationalFields::UTC)->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT);
    }
}
