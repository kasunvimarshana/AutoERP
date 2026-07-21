<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Modules\Core\Services\DecimalMath;

final class VehicleRentalReportValueFormatter
{
    public function __construct(private readonly DecimalMath $math) {}

    public function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? 0));
    }

    public function nullableDecimal(mixed $value): ?string
    {
        return $value === null ? null : $this->decimal($value);
    }

    public function party(mixed $code, mixed $name): ?string
    {
        $value = trim(trim((string) $code).' '.trim((string) $name));

        return $value === '' ? null : $value;
    }

    public function vehicle(mixed $registrationNumber, mixed $vehicleNumber): ?string
    {
        $registrationNumber = trim((string) $registrationNumber);
        $vehicleNumber = trim((string) $vehicleNumber);

        return $registrationNumber !== '' ? $registrationNumber : ($vehicleNumber !== '' ? $vehicleNumber : null);
    }

    public function date(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->toDateString();
    }

    public function dateTime(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->toDateTimeString();
    }
}
