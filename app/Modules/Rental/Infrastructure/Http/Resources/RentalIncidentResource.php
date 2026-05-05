<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Rental\Domain\Entities\RentalIncident;

class RentalIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RentalIncident $incident */
        $incident = $this->resource;

        return [
            'id' => $incident->getId(),
            'tenant_id' => $incident->getTenantId(),
            'org_unit_id' => $incident->getOrgUnitId(),
            'rental_booking_id' => $incident->getRentalBookingId(),
            'asset_id' => $incident->getAssetId(),
            'incident_type' => $incident->getIncidentType(),
            'status' => $incident->getStatus(),
            'occurred_at' => $incident->getOccurredAt(),
            'reported_by' => $incident->getReportedBy(),
            'description' => $incident->getDescription(),
            'estimated_cost' => $incident->getEstimatedCost(),
            'recovered_amount' => $incident->getRecoveredAmount(),
            'recovery_status' => $incident->getRecoveryStatus(),
            'metadata' => $incident->getMetadata(),
            'row_version' => $incident->getRowVersion(),
            'created_at' => $incident->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $incident->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
