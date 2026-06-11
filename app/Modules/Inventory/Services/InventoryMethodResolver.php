<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryMethodResolver
{
    public function valuation(Item $item, int $warehouseId, ?int $organizationUnitId): ValuationMethod
    {
        $itemMethod = $this->metadataValue($item->metadata, 'valuation_method');
        if ($itemMethod !== null) {
            return $this->valuationMethod($itemMethod);
        }

        $costing = $item->costing_method instanceof CostingMethod
            ? $item->costing_method->value
            : (string) $item->costing_method;
        if ($costing !== '' && $costing !== CostingMethod::None->value) {
            return $this->valuationMethod($costing);
        }

        return $this->valuationMethod(
            $this->metadataValue($item->category?->metadata, 'valuation_method')
            ?? $this->warehouseMetadata($warehouseId, 'valuation_method')
            ?? $this->organizationSetting((int) $item->tenant_id, $organizationUnitId, 'inventory.valuation_method')
            ?? $this->tenantSetting((int) $item->tenant_id, 'inventory.valuation_method')
            ?? $this->globalSetting('inventory.valuation_method')
            ?? (string) config('inventory.valuation.default', 'fifo'),
        );
    }

    public function allocation(Item $item, int $warehouseId, ?int $organizationUnitId): AllocationMethod
    {
        $itemMethod = $this->metadataValue($item->metadata, 'allocation_method');
        if ($itemMethod !== null) {
            return $this->allocationMethod($itemMethod);
        }

        $tracking = $item->tracking_type instanceof TrackingType
            ? $item->tracking_type
            : TrackingType::from((string) $item->tracking_type);
        if ($tracking === TrackingType::Serial) {
            return AllocationMethod::Serial;
        }
        if (in_array($tracking, [TrackingType::Batch, TrackingType::Lot], true)) {
            return AllocationMethod::Batch;
        }

        return $this->allocationMethod(
            $this->metadataValue($item->category?->metadata, 'allocation_method')
            ?? $this->warehouseMetadata($warehouseId, 'allocation_method')
            ?? $this->organizationSetting((int) $item->tenant_id, $organizationUnitId, 'inventory.allocation_method')
            ?? $this->tenantSetting((int) $item->tenant_id, 'inventory.allocation_method')
            ?? $this->globalSetting('inventory.allocation_method')
            ?? (string) config('inventory.allocation.default', 'fifo'),
        );
    }

    private function valuationMethod(string $method): ValuationMethod
    {
        return match (strtolower(trim($method))) {
            'fifo' => ValuationMethod::FIFO,
            'weighted_average' => ValuationMethod::WeightedAverage,
            'standard', 'standard_cost' => ValuationMethod::Standard,
            'manual', 'manual_cost' => ValuationMethod::Manual,
            default => throw new InvalidArgumentException("Unsupported inventory valuation method [{$method}]."),
        };
    }

    private function allocationMethod(string $method): AllocationMethod
    {
        return AllocationMethod::tryFrom(strtolower(trim($method)))
            ?? throw new InvalidArgumentException("Unsupported inventory allocation method [{$method}].");
    }

    private function metadataValue(mixed $metadata, string $key): ?string
    {
        if (! is_array($metadata)) {
            return null;
        }

        $value = data_get($metadata, 'inventory.'.$key, $metadata[$key] ?? null);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function warehouseMetadata(int $warehouseId, string $key): ?string
    {
        $warehouse = WarehouseModel::query()->find($warehouseId);

        return $warehouse instanceof WarehouseModel
            ? $this->metadataValue($warehouse->metadata, $key)
            : null;
    }

    private function organizationSetting(int $tenantId, ?int $organizationUnitId, string $key): ?string
    {
        if ($organizationUnitId === null) {
            return null;
        }

        $value = DB::table('organization_unit_settings')
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('key', $key)
            ->orderByDesc('id')
            ->value('value');

        return $this->stringValue($value);
    }

    private function tenantSetting(int $tenantId, string $key): ?string
    {
        $value = DB::table('tenant_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->whereNull('deleted_at')
            ->value('value');
        if ($this->stringValue($value) !== null) {
            return $this->stringValue($value);
        }

        return $this->stringValue(DB::table('tenant_configurations')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->whereNull('deleted_at')
            ->value('value'));
    }

    private function globalSetting(string $key): ?string
    {
        return $this->stringValue(DB::table('system_configurations')
            ->where('key', $key)
            ->whereNull('deleted_at')
            ->value('value'));
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);
        if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
            $decoded = json_decode($trimmed, true);

            return is_string($decoded) && $decoded !== '' ? $decoded : null;
        }

        return $trimmed;
    }
}
