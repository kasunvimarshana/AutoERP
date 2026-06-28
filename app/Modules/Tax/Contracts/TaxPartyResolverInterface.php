<?php

declare(strict_types=1);

namespace Modules\Tax\Contracts;

use Modules\Tax\Data\TaxPartySnapshot;

interface TaxPartyResolverInterface
{
    public const TAG = 'tax.party_resolver';

    public function partyType(): string;

    public function resolve(int $tenantId, ?int $organizationUnitId, int $partyId): TaxPartySnapshot;
}
