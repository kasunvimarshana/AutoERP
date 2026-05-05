<?php

declare(strict_types=1);

namespace Modules\Driver\Application\DTOs;

class CreateDriverLicenseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $driverId,
        public readonly string $licenseNumber,
        public readonly string $licenseClass,
        public readonly ?string $issuedCountry,
        public readonly ?string $issueDate,
        public readonly ?string $expiryDate,
        public readonly ?string $filePath,
    ) {}
}
