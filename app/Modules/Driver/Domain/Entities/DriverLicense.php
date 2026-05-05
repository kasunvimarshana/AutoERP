<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\Entities;

class DriverLicense
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $driverId,
        public string $licenseNumber,
        public string $licenseClass,
        public ?string $issuedCountry,
        public ?string $issueDate,
        public ?string $expiryDate,
        public ?string $filePath,
        public bool $isActive,
    ) {}
}
