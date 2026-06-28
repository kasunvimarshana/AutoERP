<?php

declare(strict_types=1);

namespace Modules\Tax\Contracts;

use Modules\Tax\Data\TaxItemContext;

interface TaxItemContextProviderInterface
{
    public function find(int $tenantId, ?int $organizationUnitId, int $itemId): ?TaxItemContext;
}
