<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Item\Contracts\InventoryBaseUomConversionInterface;
use Modules\Item\Models\Item;

final class ItemBaseUomConversionPreviewService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryBaseUomConversionInterface $inventoryConversion,
        private readonly ItemBaseUomConversionValidator $validator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(
        Item $item,
        int $newBaseUomId,
        ?string $providedFactor = null,
        ?string $effectiveAt = null,
    ): array {
        $validation = $this->validator->validate($item, $newBaseUomId, $providedFactor, $effectiveAt);
        $factor = $validation['conversion_factor'];
        $rows = [];

        if (is_string($factor) && $this->math->compare($factor, '0') > 0) {
            $rows = $this->inventoryConversion->preview($item, $factor)->previewRows;
        }

        return [
            'item' => $item,
            'old_base_uom' => $validation['old_uom'],
            'new_base_uom' => $validation['new_uom'],
            'conversion_factor' => $factor,
            'factor_source' => $validation['factor_source'],
            'effective_at' => $validation['effective_at'],
            'is_valid' => $validation['is_valid'],
            'blockers' => $validation['blockers'],
            'warnings' => $validation['warnings'],
            'affected_modules' => $validation['audit']['affected_modules'],
            'preview_rows' => $rows,
        ];
    }

}
