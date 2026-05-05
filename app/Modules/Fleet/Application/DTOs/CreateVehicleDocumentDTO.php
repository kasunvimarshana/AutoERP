<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\DTOs;

final class CreateVehicleDocumentDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly string $documentType,
        public readonly ?string $documentNumber = null,
        public readonly ?string $issuingAuthority = null,
        public readonly ?string $issueDate = null,
        public readonly ?string $expiryDate = null,
        public readonly ?string $filePath = null,
        public readonly ?string $notes = null,
        public readonly bool $isActive = true,
    ) {}
}
