<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use InvalidArgumentException;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryMethodResolver
{
    public function __construct(private readonly ConfigurationResolverInterface $configuration) {}

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

        $tenantId = (int) $item->tenant_id;
        $inherited = $this->metadataValue($item->category?->metadata, 'valuation_method')
            ?? $this->warehouseMetadata($warehouseId, $tenantId, 'valuation_method');

        if ($inherited !== null) {
            return $this->valuationMethod($inherited);
        }

        $configured = $this->configuration->value(
            'inventory.valuation_method',
            $tenantId,
            $organizationUnitId,
        );

        return $this->valuationMethod(
            is_string($configured)
                ? $configured
                : (string) config('inventory.valuation.default', 'fifo'),
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

        $tenantId = (int) $item->tenant_id;
        $inherited = $this->metadataValue($item->category?->metadata, 'allocation_method')
            ?? $this->warehouseMetadata($warehouseId, $tenantId, 'allocation_method');

        if ($inherited !== null) {
            return $this->allocationMethod($inherited);
        }

        $configured = $this->configuration->value(
            'inventory.allocation_method',
            $tenantId,
            $organizationUnitId,
        );

        return $this->allocationMethod(
            is_string($configured)
                ? $configured
                : (string) config('inventory.allocation.default', 'fifo'),
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

    private function warehouseMetadata(int $warehouseId, int $tenantId, string $key): ?string
    {
        $warehouse = WarehouseModel::query()
            ->where('tenant_id', $tenantId)
            ->find($warehouseId);

        return $warehouse instanceof WarehouseModel
            ? $this->metadataValue($warehouse->metadata, $key)
            : null;
    }
}
