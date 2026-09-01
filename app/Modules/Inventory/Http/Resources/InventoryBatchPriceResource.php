<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryBatchPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'batch' => $this->whenLoaded('batch', fn (): array => [
                'id' => (int) $this->batch->getKey(),
                'code' => $this->batch->batch_number,
                'name' => $this->batch->lot_number ?: $this->batch->batch_number,
                'batch_number' => $this->batch->batch_number,
                'lot_number' => $this->batch->lot_number,
            ]),
            'organization_unit' => $this->whenLoaded('organizationUnit', fn (): ?array => $this->organizationUnit === null ? null : [
                'id' => (int) $this->organizationUnit->getKey(),
                'code' => $this->organizationUnit->code,
                'name' => $this->organizationUnit->name,
            ]),
            'price_type' => $this->price_type instanceof \BackedEnum ? $this->price_type->value : (string) $this->price_type,
            'currency' => $this->whenLoaded('currency', fn (): array => [
                'id' => (int) $this->currency->getKey(),
                'code' => $this->currency->code,
                'name' => $this->currency->name,
            ]),
            'uom' => $this->whenLoaded('uom', fn (): array => [
                'id' => (int) $this->uom->getKey(),
                'code' => $this->uom->code,
                'name' => $this->uom->name,
            ]),
            'amount' => (string) $this->amount,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'revision_no' => (int) $this->revision_no,
            'recorded_from' => $this->recorded_from?->toISOString(),
            'recorded_to' => $this->recorded_to?->toISOString(),
            'correction_reason' => $this->correction_reason,
            'row_version' => (int) $this->row_version,
            'is_current_revision' => $this->recorded_to === null,
        ];
    }
}
