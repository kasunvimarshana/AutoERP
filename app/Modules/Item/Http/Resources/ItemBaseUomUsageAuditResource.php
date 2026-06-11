<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ItemBaseUomUsageAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'item' => (new ItemSummaryResource($this->resource['item']))->resolve($request),
            'has_usage' => (bool) $this->resource['has_usage'],
            'can_direct_edit' => (bool) $this->resource['can_direct_edit'],
            'usage_count' => (int) $this->resource['usage_count'],
            'affected_modules' => $this->resource['affected_modules'],
            'blockers' => $this->resource['blockers'],
            'warnings' => $this->resource['warnings'],
        ];
    }
}
