<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\UOM\Services\UomConversionService;

final class PurchaseUomService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly UomConversionService $conversions,
    ) {}

    /**
     * @return array{ordered_uom_id: int, base_uom_id: int, conversion_factor: string, base_quantity: string}
     */
    public function resolveLineUom(int $tenantId, Item $item, int $orderedUomId, string $quantity): array
    {
        $baseUomId = (int) ($item->base_uom_id ?: $orderedUomId);
        $factor = $this->conversionFactor($tenantId, (int) $item->getKey(), $orderedUomId, $baseUomId);

        return [
            'ordered_uom_id' => $orderedUomId,
            'base_uom_id' => $baseUomId,
            'conversion_factor' => $factor,
            'base_quantity' => $this->math->mul($quantity, $factor),
        ];
    }

    private function conversionFactor(int $tenantId, int $itemId, int $fromUomId, int $toUomId): string
    {
        if ($fromUomId === $toUomId) {
            return '1.000000';
        }

        $itemUnit = ItemUnit::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->where('uom_id', $fromUomId)
            ->where('is_active', true)
            ->first();

        if ($itemUnit instanceof ItemUnit) {
            return $this->math->normalize((string) $itemUnit->conversion_factor);
        }

        $result = $this->conversions->getConversionFactor($fromUomId, $toUomId, $tenantId);
        if ($result->isSuccess()) {
            return $this->math->normalize((string) $result->valueOrFail());
        }

        throw new InvalidArgumentException('Purchase UOM conversion is required but no conversion exists.');
    }
}
