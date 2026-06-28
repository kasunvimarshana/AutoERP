<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Tax\Contracts\TaxPartyResolverInterface;
use Modules\Tax\Data\TaxPartySnapshot;

final class CustomerTaxPartyResolver implements TaxPartyResolverInterface
{
    public function partyType(): string
    {
        return 'customer';
    }

    public function resolve(int $tenantId, ?int $organizationUnitId, int $partyId): TaxPartySnapshot
    {
        $customer = Customer::query()->findOrFail($partyId);
        if ((int) $customer->tenant_id !== $tenantId
            || ($organizationUnitId !== null
                && $customer->organization_unit_id !== null
                && (int) $customer->organization_unit_id !== $organizationUnitId)) {
            throw new InvalidArgumentException('Customer tax profile belongs to a different scope.');
        }

        return new TaxPartySnapshot(
            partyType: 'customer',
            partyId: (int) $customer->getKey(),
            tenantId: (int) $customer->tenant_id,
            organizationUnitId: $customer->organization_unit_id === null ? null : (int) $customer->organization_unit_id,
            code: (string) $customer->code,
            name: (string) $customer->name,
        );
    }
}
