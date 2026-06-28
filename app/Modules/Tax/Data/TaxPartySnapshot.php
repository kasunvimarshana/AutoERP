<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxPartySnapshot
{
    public function __construct(
        public string $partyType,
        public int $partyId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $code,
        public string $name,
    ) {}
}
