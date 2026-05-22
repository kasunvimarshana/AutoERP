<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Services;

use Modules\Inventory\Domain\Contracts\UomConversionServiceContract;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversion;

class EloquentUomConversionService implements UomConversionServiceContract
{
    public function toBaseQuantity(
        int $tenantId,
        ?int $itemId,
        int $fromUomId,
        int $toUomId,
        float $quantity
    ): float {
        if ($fromUomId === $toUomId) {
            return round($quantity, 4);
        }

        $direct = UomConversion::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->where(function ($q) use ($itemId): void {
                $q->whereNull('item_id');
                if ($itemId !== null) {
                    $q->orWhere('item_id', $itemId);
                }
            })
            ->orderByRaw('CASE WHEN item_id IS NULL THEN 1 ELSE 0 END')
            ->first();

        if ($direct !== null) {
            return round($quantity * (float) $direct->factor, 4);
        }

        $reverse = UomConversion::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('from_uom_id', $toUomId)
            ->where('to_uom_id', $fromUomId)
            ->where(function ($q) use ($itemId): void {
                $q->whereNull('item_id');
                if ($itemId !== null) {
                    $q->orWhere('item_id', $itemId);
                }
            })
            ->orderByRaw('CASE WHEN item_id IS NULL THEN 1 ELSE 0 END')
            ->first();

        if ($reverse !== null && (float) $reverse->factor > 0.0) {
            return round($quantity / (float) $reverse->factor, 4);
        }

        return round($quantity, 4);
    }
}
