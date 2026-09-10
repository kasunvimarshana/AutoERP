<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\OperationalFields;

final class OperationalTime
{
    public static function parse(mixed $value, string $field): CarbonImmutable
    {
        Validator::make([$field => $value], [$field => ['required', 'string', 'date_format:'.OperationalFields::TIMESTAMP_FORMAT]])->validate();

        return CarbonImmutable::createFromFormat(OperationalFields::TIMESTAMP_FORMAT, $value);
    }

    /** @return array{CarbonImmutable, ?CarbonImmutable} */
    public static function plannedPeriod(mixed $startsAt, mixed $endsAt): array
    {
        $start = self::parse($startsAt, 'starts_at');
        $end = $endsAt === null ? null : self::parse($endsAt, 'ends_at');
        if ($end !== null && $end <= $start) {
            throw ValidationException::withMessages(['ends_at' => ['End must be later than start.']]);
        }

        return [$start, $end];
    }

    public static function database(CarbonImmutable $value): string
    {
        return $value->setTimezone(OperationalFields::UTC)->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT);
    }
}
