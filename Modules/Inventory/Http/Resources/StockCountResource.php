<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;

class StockCountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'warehouse_id' => $this->warehouse_id,
            'count_number' => $this->count_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'count_date' => $this->count_date?->toDateString(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'reconciled_at' => $this->reconciled_at?->toISOString(),
            'counted_by' => $this->counted_by,
            'approved_by' => $this->approved_by,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'items' => StockCountItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),
            'items_with_variance' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->filter(function ($item) {
                    if ($item->counted_quantity === null || $item->system_quantity === null) {
                        return false;
                    }
                    $variance = MathHelper::subtract($item->counted_quantity, $item->system_quantity);

                    return ! MathHelper::equals($variance, '0');
                })->count()
            ),
            'total_variance_value' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->calculateTotalVarianceValue()
            ),
            'can_start' => $this->status->canStart(),
            'can_modify' => $this->status->canModify(),
            'can_cancel' => $this->status->canCancel(),
            'is_in_progress' => $this->status->isInProgress(),
            'is_completed' => $this->status->isCompleted(),
            'is_reconciled' => $this->status->isReconciled(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    /**
     * Calculate total variance value.
     */
    private function calculateTotalVarianceValue(): string
    {
        if (! $this->relationLoaded('items')) {
            return '0';
        }

        $totalVariance = '0';

        foreach ($this->items as $item) {
            if ($item->counted_quantity === null || $item->system_quantity === null) {
                continue;
            }

            $variance = MathHelper::subtract($item->counted_quantity, $item->system_quantity);

            if ($item->relationLoaded('stockItem') && $item->stockItem) {
                $varianceValue = MathHelper::multiply($variance, $item->stockItem->average_cost);
                $totalVariance = MathHelper::add($totalVariance, $varianceValue);
            }
        }

        return $totalVariance;
    }
}
