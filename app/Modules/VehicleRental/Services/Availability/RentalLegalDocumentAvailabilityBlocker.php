<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Availability;

use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Models\VehicleDocument;

final class RentalLegalDocumentAvailabilityBlocker implements VehicleAvailabilityBlockerInterface
{
    private const REQUIRED_DOCUMENT_TYPES = [
        VehicleDocumentType::Insurance,
        VehicleDocumentType::RevenueLicense,
    ];

    public function blockingReason(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startsAt,
        ?string $endsAt,
    ): ?string {
        $requiredUntil = substr($endsAt ?? $startsAt, 0, 10);
        foreach (self::REQUIRED_DOCUMENT_TYPES as $documentType) {
            $valid = VehicleDocument::query()
                ->where('tenant_id', $tenantId)
                ->where('vehicle_id', $vehicleId)
                ->where('document_type', $documentType->value)
                ->where('status', VehicleDocumentStatus::Active->value)
                ->where(function ($query) use ($requiredUntil): void {
                    $query->whereNull('expiry_date')
                        ->orWhereDate('expiry_date', '>=', $requiredUntil);
                })
                ->exists();
            if (! $valid) {
                return sprintf(
                    'The selected vehicle requires an active %s document covering the requested rental period.',
                    str_replace('_', ' ', $documentType->value),
                );
            }
        }

        return null;
    }
}
