<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Customer\DTOs\CustomerResultData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;

final class CustomerLookupService
{
    public function activeCustomers(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->active()->get();
    }

    public function customersByCategory(int $tenantId, int $categoryId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($categoryId)->where('is_active', true))
            ->get();
    }

    public function customersAllowedForCredit(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->whereHas('creditProfile', static fn (Builder $profile): Builder => $profile
                ->where('credit_allowed', true)
                ->where('is_active', true))
            ->get();
    }

    public function restrictedCustomers(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->whereIn('status', [CustomerStatus::OnHold->value, CustomerStatus::Blacklisted->value])
            ->get();
    }

    public function customersOnHold(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->where('status', CustomerStatus::OnHold)
            ->get();
    }

    public function blacklistedCustomers(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->where('status', CustomerStatus::Blacklisted)
            ->get();
    }

    public function result(Customer $customer): CustomerResultData
    {
        $profile = $customer->relationLoaded('creditProfile')
            ? $customer->creditProfile
            : $customer->creditProfile()->first();

        return new CustomerResultData(
            customerId: (int) $customer->getKey(),
            tenantId: (int) $customer->tenant_id,
            organizationUnitId: $customer->organization_unit_id,
            customerNumber: (string) $customer->customer_number,
            code: (string) $customer->code,
            name: (string) $customer->name,
            customerType: $customer->customer_type,
            status: $customer->status,
            creditLimit: (string) ($profile?->credit_limit ?? '0.000000'),
            creditAllowed: (bool) ($profile?->credit_allowed ?? false),
            advanceAllowed: (bool) ($profile?->advance_allowed ?? false),
            isTaxExempt: (bool) $customer->is_tax_exempt,
        );
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Customer::query()->forTenant($tenantId, $organizationUnitId)->with('creditProfile');
    }
}
