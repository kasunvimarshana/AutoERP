<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            ...(new ItemSummaryResource($this->resource))->resolve($request),
            'description' => $this->description,
            'metadata' => $this->metadata,
            'units' => $this->whenLoaded('units', fn () => ItemUnitResource::collection($this->units)->resolve($request)),
            'variants' => $this->whenLoaded('variants', fn () => ItemVariantResource::collection($this->variants)->resolve($request)),
            'bundles' => $this->whenLoaded('bundleLines', fn () => ItemBundleResource::collection($this->bundleLines)->resolve($request)),
            'prices' => $this->whenLoaded('prices', fn () => ItemPriceResource::collection($this->prices)->resolve($request)),
            'codes' => $this->whenLoaded('codes', fn () => ItemCodeResource::collection($this->codes)->resolve($request)),
            'usage_rules' => $this->whenLoaded('usageRules', fn () => ItemUsageRuleResource::collection($this->usageRules)->resolve($request)),
        ];
    }
}
