<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\DTOs;

final readonly class OrganizationUnitLegalProfileSnapshot
{
    public function __construct(
        public int $tenantId,
        public int $organizationUnitId,
        public string $legalName,
        public ?string $tin,
        public ?string $vatRegistrationNumber,
        public ?string $svatRegistrationNumber,
        public string $address,
        public ?string $phone,
        public ?string $email,
    ) {}
}
