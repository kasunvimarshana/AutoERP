<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'name' => $this->resource->getName(),
            'type' => $this->resource->getType(),
            'serial_number' => $this->resource->getSerialNumber(),
            'asset_owner_id' => $this->resource->getAssetOwnerId(),
            'purchase_date' => $this->resource->getPurchaseDate()?->format('Y-m-d'),
            'acquisition_cost' => $this->resource->getAcquisitionCost(),
            'status' => $this->resource->getStatus(),
            'depreciation_method' => $this->resource->getDepreciationMethod(),
            'useful_life_years' => $this->resource->getUsefulLifeYears(),
            'salvage_value' => $this->resource->getSalvageValue(),
        ];
    }
}
