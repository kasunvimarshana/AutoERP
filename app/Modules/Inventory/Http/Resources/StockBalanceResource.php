<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Enums\InventoryStockLevel;

final class StockBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $available = (float) ($data['quantity_available'] ?? 0);
        $reorderLevel = $this->item?->reorder_level;
        $stockLevel = $available <= 0
            ? InventoryStockLevel::OUT_OF_STOCK
            : ($reorderLevel !== null && $available <= (float) $reorderLevel
                ? InventoryStockLevel::LOW_STOCK
                : InventoryStockLevel::IN_STOCK);

        return [
            ...$data,
            'reorder_level' => $reorderLevel === null ? null : (string) $reorderLevel,
            'stock_level' => $stockLevel->value,
            'quantity_basis' => 'base',
            'base_quantity_on_hand' => $data['quantity_on_hand'] ?? '0.000000',
            'base_quantity_reserved' => $data['quantity_reserved'] ?? '0.000000',
            'base_quantity_allocated' => $data['quantity_allocated'] ?? '0.000000',
            'base_quantity_available' => $data['quantity_available'] ?? '0.000000',
        ];
    }
}
