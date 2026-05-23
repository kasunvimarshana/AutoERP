<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;

class WarehouseDomainService
{
    /** @var array<int, string> */
    private const WAREHOUSE_TYPES = ['standard', 'virtual', 'transit', 'quarantine'];

    /** @var array<int, string> */
    private const LOCATION_TYPES = ['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'];

    public function normalizeRequiredText(string $value, string $field): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf('%s cannot be empty.', $field));
        }

        return $normalized;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeCode(?string $code): ?string
    {
        $normalized = $this->normalizeOptionalText($code);

        return $normalized === null ? null : Str::upper($normalized);
    }

    public function normalizeWarehouseType(string $type): string
    {
        $normalized = Str::lower(trim($type));

        if (! in_array($normalized, self::WAREHOUSE_TYPES, true)) {
            throw new InvalidArgumentException('Warehouse type is invalid.');
        }

        return $normalized;
    }

    public function normalizeLocationType(string $type): string
    {
        $normalized = Str::lower(trim($type));

        if (! in_array($normalized, self::LOCATION_TYPES, true)) {
            throw new InvalidArgumentException('Warehouse location type is invalid.');
        }

        return $normalized;
    }

    public function normalizeDepth(int $depth): int
    {
        if ($depth < 0) {
            throw new InvalidArgumentException('Depth must be greater than or equal to 0.');
        }

        return $depth;
    }

    public function normalizeCapacity(null|int|float|string $capacity): null|int|float|string
    {
        if ($capacity === null || $capacity === '') {
            return null;
        }

        if (! is_numeric($capacity) || (float) $capacity < 0) {
            throw new InvalidArgumentException('Capacity must be a non-negative number.');
        }

        return $capacity;
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
