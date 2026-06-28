<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Contracts\TaxPartyResolverInterface;
use Modules\Tax\Data\TaxPartySnapshot;

final class SupplierTaxPartyResolver implements TaxPartyResolverInterface
{
    public function partyType(): string
    {
        return 'supplier';
    }

    public function resolve(int $tenantId, ?int $organizationUnitId, int $partyId): TaxPartySnapshot
    {
        $supplier = Supplier::query()->findOrFail($partyId);
        if ((int) $supplier->tenant_id !== $tenantId
            || ($organizationUnitId !== null
                && $supplier->organization_unit_id !== null
                && (int) $supplier->organization_unit_id !== $organizationUnitId)) {
            throw new InvalidArgumentException('Supplier tax profile belongs to a different scope.');
        }

        return new TaxPartySnapshot(
            partyType: 'supplier',
            partyId: (int) $supplier->getKey(),
            tenantId: (int) $supplier->tenant_id,
            organizationUnitId: $supplier->organization_unit_id === null ? null : (int) $supplier->organization_unit_id,
            code: (string) $supplier->code,
            name: (string) $supplier->name,
        );
    }
}
