<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VehicleDomainService
{
    /** @var array<int, string> */
    private const USAGE_PROFILES = ['rent_only', 'service_only', 'dual', 'internal'];

    /** @var array<int, string> */
    private const STATUSES = ['active', 'inactive'];

    public function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeVehicleCode(?string $vehicleCode): ?string
    {
        $value = $this->normalizeText($vehicleCode);

        return $value === null ? null : Str::upper($value);
    }

    public function normalizeVin(?string $vin): ?string
    {
        $value = $this->normalizeText($vin);

        if ($value === null) {
            return null;
        }

        return Str::upper(str_replace(' ', '', $value));
    }

    public function normalizeUsageProfile(string $usageProfile): string
    {
        $value = Str::lower(trim($usageProfile));

        if (! in_array($value, self::USAGE_PROFILES, true)) {
            throw new InvalidArgumentException('Usage profile must be rent_only, service_only, dual, or internal.');
        }

        return $value;
    }

    public function normalizeStatus(string $status): string
    {
        $value = Str::lower(trim($status));

        if (! in_array($value, self::STATUSES, true)) {
            throw new InvalidArgumentException('Status must be active or inactive.');
        }

        return $value;
    }

    public function normalizeYear(?int $year): ?int
    {
        if ($year === null) {
            return null;
        }

        $currentYear = (int) CarbonImmutable::now()->format('Y');

        if ($year < 1900 || $year > $currentYear + 1) {
            throw new InvalidArgumentException('Year is out of valid range.');
        }

        return $year;
    }

    public function normalizeNullablePositiveInt(?int $value, string $field): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('%s must be greater than or equal to 0.', $field));
        }

        return $value;
    }

    public function normalizePositiveInt(int $value, string $field): int
    {
        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('%s must be greater than or equal to 0.', $field));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }
}
