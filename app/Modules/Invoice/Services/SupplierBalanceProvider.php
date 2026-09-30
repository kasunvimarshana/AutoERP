<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Supplier\Contracts\SupplierBalanceProviderInterface;

final class SupplierBalanceProvider implements SupplierBalanceProviderInterface
{
    public function __construct(private readonly InvoiceBalanceProviderInterface $balances) {}

    public function getOutstandingTotals(int $tenantId, ?int $organizationUnitId, array $supplierIds): array
    {
        return $this->balances->getOutstandingTotalsForParties($tenantId, $organizationUnitId, InvoicePartyType::Supplier->value, $supplierIds);
    }
}
