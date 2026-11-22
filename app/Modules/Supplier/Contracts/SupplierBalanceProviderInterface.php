<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\Contracts;

interface SupplierBalanceProviderInterface
{
    /**
     * @param  list<int>  $supplierIds
     * @return array<int, list<array{amount: string, currency_code: string|null}>>
     */
    public function getOutstandingTotals(int $tenantId, ?int $organizationUnitId, array $supplierIds): array;
}
