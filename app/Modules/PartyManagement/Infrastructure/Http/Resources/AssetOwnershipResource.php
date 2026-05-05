<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PartyManagement\Domain\Entities\AssetOwnership;

class AssetOwnershipResource extends JsonResource
{
    public function __construct(private readonly AssetOwnership $ownership)
    {
        parent::__construct($ownership);
    }

    public function toArray($request): array
    {
        return [
            'id'               => $this->ownership->getId(),
            'tenant_id'        => $this->ownership->getTenantId(),
            'party_id'         => $this->ownership->getPartyId(),
            'asset_id'         => $this->ownership->getAssetId(),
            'ownership_type'   => $this->ownership->getOwnershipType(),
            'acquisition_date' => $this->ownership->getAcquisitionDate()->format('Y-m-d'),
            'disposal_date'    => $this->ownership->getDisposalDate()?->format('Y-m-d'),
            'acquisition_cost' => $this->ownership->getAcquisitionCost(),
            'is_active'        => $this->ownership->isActive(),
            'notes'            => $this->ownership->getNotes(),
        ];
    }
}
