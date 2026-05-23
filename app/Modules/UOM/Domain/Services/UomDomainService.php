<?php

declare(strict_types=1);

namespace Modules\UOM\Domain\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Exceptions\UomIntegrityException;
use Modules\UOM\Domain\Exceptions\UomRecordNotFoundException;

class UomDomainService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $units,
        private readonly UomConversionRepositoryInterface $conversions,
    ) {}

    public function normalizeText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function normalizeSymbol(string $symbol): string
    {
        return trim($symbol);
    }

    public function normalizeType(string $type): string
    {
        $type = strtoupper(trim($type));

        if (! in_array($type, config('uom.types', []), true)) {
            throw UomIntegrityException::rule("Unsupported UOM type [{$type}].");
        }

        return $type;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeFactor(string|int|float $factor): string
    {
        $factor = $this->decimal($factor);

        if ($this->compare($factor, '0') <= 0) {
            throw UomIntegrityException::rule('UOM conversion factor must be greater than zero.');
        }

        return $factor;
    }

    public function assertSameTenantUnit(int|string $tenantId, int|string $unitId, string $label): Model
    {
        $unit = $this->units->findForTenantById($tenantId, $unitId);

        if ($unit === null) {
            throw UomRecordNotFoundException::for($label, $unitId);
        }

        return $unit;
    }

    public function assertDifferentUnits(int|string $fromUomId, int|string $toUomId): void
    {
        if ((string) $fromUomId === (string) $toUomId) {
            throw UomIntegrityException::rule('From UOM and to UOM must be different.');
        }
    }

    public function assertUniqueConversion(
        int|string $tenantId,
        int|string $fromUomId,
        int|string $toUomId,
        int|string|null $itemId = null,
        int|string|null $excludeId = null
    ): void {
        $existing = $this->conversions->findForScope($tenantId, $fromUomId, $toUomId, $itemId, $excludeId);

        if ($existing !== null) {
            throw UomIntegrityException::conflict('A UOM conversion already exists for this tenant, item, and UOM pair.');
        }
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw UomIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    public function convert(
        int|string $tenantId,
        int|string $fromUomId,
        int|string $toUomId,
        string|int|float $quantity,
        int|string|null $itemId = null
    ): string {
        if ((string) $fromUomId === (string) $toUomId) {
            return $this->decimal($quantity);
        }

        $conversion = $this->conversions->findActiveConversion($tenantId, $fromUomId, $toUomId, $itemId);

        if ($conversion !== null) {
            return $this->mul($quantity, $conversion->factor);
        }

        $inverse = $this->conversions->findActiveConversion($tenantId, $toUomId, $fromUomId, $itemId);

        if ($inverse !== null && (bool) $inverse->is_bidirectional) {
            return $this->div($quantity, $inverse->factor);
        }

        throw UomRecordNotFoundException::for('Active UOM conversion', "{$fromUomId}:{$toUomId}");
    }

    public function decimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('uom.precision.scale', 4), '.', '');
    }

    private function mul(string|int|float $left, string|int|float $right): string
    {
        return $this->decimal((float) $left * (float) $right);
    }

    private function div(string|int|float $left, string|int|float $right): string
    {
        if ($this->compare((string) $right, '0') === 0) {
            throw UomIntegrityException::rule('UOM conversion factor cannot be zero.');
        }

        return $this->decimal((float) $left / (float) $right);
    }

    private function compare(string $left, string $right): int
    {
        return (float) $left <=> (float) $right;
    }
}
