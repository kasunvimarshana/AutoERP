<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDepreciationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'asset_id' => $this->resource->getAssetId(),
            'depreciation_amount' => $this->resource->getDepreciationAmount(),
            'book_value_before' => $this->resource->getBookValueBefore(),
            'book_value_after' => $this->resource->getBookValueAfter(),
            'fiscal_period_id' => $this->resource->getFiscalPeriodId(),
            'status' => $this->resource->getStatus(),
            'posted_date' => $this->resource->getPostedDate()?->format('Y-m-d H:i:s'),
        ];
    }
}
