<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Data;

final readonly class OrganizationUnitBrandingProfile
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $logoDataUri,
    ) {}
}
