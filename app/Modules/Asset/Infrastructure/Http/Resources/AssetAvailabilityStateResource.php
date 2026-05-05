<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetAvailabilityStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'asset_id' => $this->getAssetId(),
            'availability_status' => $this->getAvailabilityStatus(),
            'reason_code' => $this->getReasonCode(),
            'source_type' => $this->getSourceType(),
            'source_id' => $this->getSourceId(),
            'updated_by' => $this->getUpdatedBy(),
            'effective_from' => $this->getEffectiveFrom()->format('c'),
            'effective_to' => $this->getEffectiveTo()?->format('c'),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
