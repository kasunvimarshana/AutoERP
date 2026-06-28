<?php

declare(strict_types=1);

namespace Modules\Tax\Contracts;

use Modules\Tax\Data\TaxPartyProfileData;

interface TaxPartyProfileReaderInterface
{
    public function supplierProfile(int $tenantId, ?int $organizationUnitId, int $supplierId): ?TaxPartyProfileData;

    public function customerProfile(int $tenantId, ?int $organizationUnitId, int $customerId): ?TaxPartyProfileData;
}
