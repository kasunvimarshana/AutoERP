<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Events;

class VehicleDocumentExpiring
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly int $documentId,
        public readonly string $documentType,
        public readonly string $expiryDate,
        public readonly int $daysUntilExpiry,
    ) {}
}
