<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tax\Contracts;

use Modules\Tax\Data\TaxItemContext;

interface TaxItemContextProviderInterface
{
    public function find(int $tenantId, ?int $organizationUnitId, int $itemId): ?TaxItemContext;
}
