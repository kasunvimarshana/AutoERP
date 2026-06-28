<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Tax\Contracts\TaxPartyProfileReaderInterface;
use Modules\Tax\Data\TaxPartyProfileData;
use Modules\Tax\Models\CustomerTaxProfile;
use Modules\Tax\Models\SupplierTaxProfile;

final class TaxPartyProfileReader implements TaxPartyProfileReaderInterface
{
    public function supplierProfile(int $tenantId, ?int $organizationUnitId, int $supplierId): ?TaxPartyProfileData
    {
        $profile = $this->profileQuery(SupplierTaxProfile::query(), $tenantId, $organizationUnitId)
            ->with('taxGroup')
            ->where('supplier_id', $supplierId)
            ->first();

        return $profile instanceof SupplierTaxProfile ? $this->map($profile) : null;
    }

    public function customerProfile(int $tenantId, ?int $organizationUnitId, int $customerId): ?TaxPartyProfileData
    {
        $profile = $this->profileQuery(CustomerTaxProfile::query(), $tenantId, $organizationUnitId)
            ->with('taxGroup')
            ->where('customer_id', $customerId)
            ->first();

        return $profile instanceof CustomerTaxProfile ? $this->map($profile) : null;
    }

    private function profileQuery(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->when(
                $organizationUnitId === null,
                fn (Builder $builder): Builder => $builder->whereNull('organization_unit_id'),
                fn (Builder $builder): Builder => $builder
                    ->where(fn (Builder $scope): Builder => $scope
                        ->whereNull('organization_unit_id')
                        ->orWhere('organization_unit_id', $organizationUnitId))
                    ->orderByRaw('case when organization_unit_id = ? then 0 else 1 end', [$organizationUnitId]),
            );
    }

    private function map(CustomerTaxProfile|SupplierTaxProfile $profile): TaxPartyProfileData
    {
        return new TaxPartyProfileData(
            profileId: (int) $profile->getKey(),
            taxGroupId: $profile->tax_group_id === null ? null : (int) $profile->tax_group_id,
            taxGroupCode: $profile->taxGroup?->code === null ? null : (string) $profile->taxGroup->code,
            taxGroupName: $profile->taxGroup?->name === null ? null : (string) $profile->taxGroup->name,
            exemptionStatus: (string) $profile->exemption_status,
            registrationNumber: $profile->registration_number === null ? null : (string) $profile->registration_number,
        );
    }
}
