<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Exceptions\InventoryIntegrityException;

class InventoryDomainService
{
    public function normalizeResourceKey(string $resource): string
    {
        return str_replace('-', '_', strtolower(trim($resource)));
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('inventory.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null, bool $uppercase = true): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = $uppercase ? strtoupper((string) $value) : strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw InventoryIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw InventoryIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("inventory.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw InventoryIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw InventoryIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareMovementAmounts(array $attributes): array
    {
        $direction = $this->normalizeEnum('direction', $attributes['direction'] ?? null, config('inventory.directions', []));
        $quantity = (float) ($attributes['quantity'] ?? 0);
        $unitCost = (float) ($attributes['unit_cost'] ?? 0);

        $attributes['direction'] = $direction;
        $attributes['quantity'] = $this->normalizeDecimal($quantity);
        $attributes['quantity_in'] = $this->normalizeDecimal($direction === 'IN' ? $quantity : 0);
        $attributes['quantity_out'] = $this->normalizeDecimal($direction === 'OUT' ? $quantity : 0);
        $attributes['unit_cost'] = array_key_exists('unit_cost', $attributes) ? $this->normalizeDecimal($unitCost) : null;
        $attributes['total_cost'] = $this->normalizeDecimal($quantity * $unitCost);
        $attributes['txn_type'] = $this->normalizeEnum('txn_type', $attributes['txn_type'] ?? null, config('inventory.movement_types', []));

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareVarianceAmounts(array $attributes): array
    {
        $systemQty = (float) ($attributes['system_qty'] ?? 0);
        $countedQty = (float) ($attributes['counted_qty'] ?? 0);
        $unitCost = (float) ($attributes['unit_cost'] ?? 0);
        $variance = $countedQty - $systemQty;

        $attributes['system_qty'] = $this->normalizeDecimal($systemQty);
        $attributes['counted_qty'] = $this->normalizeDecimal($countedQty);
        $attributes['variance_qty'] = $this->normalizeDecimal($variance);
        $attributes['unit_cost'] = $this->normalizeDecimal($unitCost);
        $attributes['variance_value'] = $this->normalizeDecimal($variance * $unitCost);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function stockDimensionCriteria(array $attributes): array
    {
        return [
            'tenant_id' => $attributes['tenant_id'],
            'item_id' => $attributes['item_id'],
            'variant_id' => $attributes['variant_id'] ?? null,
            'warehouse_id' => $attributes['warehouse_id'],
            'location_id' => $attributes['location_id'] ?? null,
            'batch_id' => $attributes['batch_id'] ?? null,
            'serial_id' => $attributes['serial_id'] ?? null,
            'condition' => $attributes['condition'] ?? config('inventory.defaults.condition', 'good'),
        ];
    }

    public function assertEnoughAvailable(float $onHand, float $reserved, float $outgoing): void
    {
        if ((bool) config('inventory.defaults.allow_negative_stock', false)) {
            return;
        }

        if (($onHand - $reserved) < $outgoing) {
            throw InventoryIntegrityException::rule('Insufficient available stock for this inventory operation.');
        }
    }

    public function assertSameTenant(Model $parent, int|string $tenantId, string $label): void
    {
        if ((string) $parent->tenant_id !== (string) $tenantId) {
            throw InventoryIntegrityException::rule("{$label} must belong to the same tenant.");
        }
    }
}
