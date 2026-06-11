<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ItemBaseUomConversionPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'item' => (new ItemSummaryResource($this->resource['item']))->resolve($request),
            'old_base_uom' => $this->uom($this->resource['old_base_uom']),
            'new_base_uom' => $this->uom($this->resource['new_base_uom']),
            'conversion_factor' => $this->resource['conversion_factor'],
            'factor_source' => $this->resource['factor_source'],
            'effective_at' => $this->resource['effective_at']?->toISOString(),
            'is_valid' => (bool) $this->resource['is_valid'],
            'affected_modules' => $this->resource['affected_modules'],
            'preview_rows' => $this->resource['preview_rows'],
            'blockers' => $this->resource['blockers'],
            'warnings' => $this->resource['warnings'],
        ];
    }

    private function uom(?Model $uom): ?array
    {
        if ($uom === null) {
            return null;
        }

        return [
            'id' => (int) $uom->getKey(),
            'code' => $uom->getAttribute('code'),
            'name' => $uom->getAttribute('name'),
            'symbol' => $uom->getAttribute('symbol'),
        ];
    }
}
