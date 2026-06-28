<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Finance\Models\FinanceAccount;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\CustomerTaxProfile;
use Modules\Tax\Models\SupplierTaxProfile;
use Modules\Tax\Models\Tax;
use Modules\Tax\Models\TaxGroup;
use Modules\Tax\Models\TaxGroupLine;
use Modules\Tax\Models\TaxPostingProfile;
use Modules\Tax\Models\TaxRate;

final class TaxMasterDataService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTax(array $data, ?Tax $tax = null): Tax
    {
        $tenantId = (int) $data['tenant_id'];
        $organizationUnitId = $this->nullableInt($data, 'organization_unit_id');
        $code = trim((string) $data['code']);
        $method = trim((string) $data['calculation_method']);

        if ($code === '') {
            throw new InvalidArgumentException('Tax code is required.');
        }
        if (! in_array($method, config('tax.calculation_methods', []), true)) {
            throw new InvalidArgumentException('Tax calculation method is invalid.');
        }
        if ($tax instanceof Tax && ((int) $tax->tenant_id !== $tenantId)) {
            throw new InvalidArgumentException('Tax tenant scope cannot be changed.');
        }

        $duplicate = Tax::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->when($tax instanceof Tax && $tax->exists, fn (Builder $query): Builder => $query->whereKeyNot($tax->getKey()))
            ->exists();
        if ($duplicate) {
            throw new InvalidArgumentException('Tax code already exists for this tenant.');
        }

        $tax ??= new Tax;
        $tax->forceFill([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'tax_type' => trim((string) $data['tax_type']),
            'calculation_method' => $method,
            'is_withholding' => (bool) ($data['is_withholding'] ?? false),
            'recoverable' => (bool) ($data['recoverable'] ?? false),
            'payable' => (bool) ($data['payable'] ?? false),
            'receivable' => (bool) ($data['receivable'] ?? false),
            'active' => (bool) ($data['active'] ?? true),
        ])->save();

        return $tax->refresh()->load('rates');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveRate(Tax $tax, array $data, ?TaxRate $rate = null): TaxRate
    {
        $effectiveFrom = (string) $data['effective_from'];
        $effectiveTo = $data['effective_to'] ?? null;
        $active = (bool) ($data['active'] ?? true);

        if ($effectiveTo !== null && $effectiveTo !== '' && strcmp((string) $effectiveTo, $effectiveFrom) < 0) {
            throw new InvalidArgumentException('Tax rate effective-to date cannot be before effective-from date.');
        }

        $normalizedRate = $this->math->normalize((string) $data['rate']);
        if ($this->math->isNegative($normalizedRate)) {
            throw new InvalidArgumentException('Tax rate cannot be negative.');
        }

        if ($active) {
            $overlap = TaxRate::query()
                ->where('tenant_id', (int) $tax->tenant_id)
                ->where('tax_id', $tax->getKey())
                ->where('active', true)
                ->when($rate instanceof TaxRate && $rate->exists, fn (Builder $query): Builder => $query->whereKeyNot($rate->getKey()))
                ->where('effective_from', '<=', $effectiveTo ?: '9999-12-31')
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $effectiveFrom))
                ->exists();

            if ($overlap) {
                throw new InvalidArgumentException('Tax rate effective dates overlap an existing active rate.');
            }
        }

        $rate ??= new TaxRate;
        $rate->forceFill([
            'tenant_id' => (int) $tax->tenant_id,
            'tax_id' => $tax->getKey(),
            'rate' => $normalizedRate,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo ?: null,
            'active' => $active,
        ])->save();

        return $rate->refresh()->load('tax');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function saveGroup(array $data, array $lines, ?TaxGroup $group = null): TaxGroup
    {
        $tenantId = (int) $data['tenant_id'];
        $organizationUnitId = $this->nullableInt($data, 'organization_unit_id');
        $code = trim((string) $data['code']);

        return DB::transaction(function () use ($tenantId, $organizationUnitId, $code, $data, $lines, $group): TaxGroup {
            if ($group instanceof TaxGroup && ((int) $group->tenant_id !== $tenantId || $group->organization_unit_id !== $organizationUnitId)) {
                throw new InvalidArgumentException('Tax group scope cannot be changed.');
            }

            $duplicate = TaxGroup::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $code)
                ->when(
                    $organizationUnitId === null,
                    fn (Builder $query): Builder => $query->whereNull('organization_unit_id'),
                    fn (Builder $query): Builder => $query->where('organization_unit_id', $organizationUnitId),
                )
                ->when($group instanceof TaxGroup && $group->exists, fn (Builder $query): Builder => $query->whereKeyNot($group->getKey()))
                ->exists();
            if ($duplicate) {
                throw new InvalidArgumentException('Tax group code already exists for this scope.');
            }

            $isDefault = (bool) ($data['is_default'] ?? false);
            if ($isDefault) {
                TaxGroup::query()
                    ->where('tenant_id', $tenantId)
                    ->when(
                        $organizationUnitId === null,
                        fn (Builder $query): Builder => $query->whereNull('organization_unit_id'),
                        fn (Builder $query): Builder => $query->where('organization_unit_id', $organizationUnitId),
                    )
                    ->update(['is_default' => false]);
            }

            $group ??= new TaxGroup;
            $group->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'code' => $code,
                'name' => trim((string) $data['name']),
                'is_default' => $isDefault,
                'active' => (bool) ($data['active'] ?? true),
            ])->save();

            $seenSequences = [];
            $group->lines()->delete();
            foreach ($lines as $line) {
                $sequence = (int) ($line['sequence'] ?? 0);
                if ($sequence < 1 || in_array($sequence, $seenSequences, true)) {
                    throw new InvalidArgumentException('Tax group sequence must be unique and greater than zero.');
                }
                $seenSequences[] = $sequence;

                $tax = Tax::query()->findOrFail((int) $line['tax_id']);
                $this->assertTaxInScope($tax, $tenantId, $organizationUnitId);
                if (! (bool) $tax->active) {
                    throw new InvalidArgumentException('Inactive taxes cannot be assigned to a tax group.');
                }

                TaxGroupLine::query()->create([
                    'tenant_id' => $tenantId,
                    'tax_group_id' => $group->getKey(),
                    'tax_id' => $tax->getKey(),
                    'sequence' => $sequence,
                    'active' => (bool) ($line['active'] ?? true),
                ]);
            }

            return $group->refresh()->load('lines.tax.rates');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveCustomerProfile(array $data, ?CustomerTaxProfile $profile = null): CustomerTaxProfile
    {
        $tenantId = (int) $data['tenant_id'];
        $organizationUnitId = $this->nullableInt($data, 'organization_unit_id');
        $customer = Customer::query()->findOrFail((int) $data['customer_id']);
        if ((int) $customer->tenant_id !== $tenantId || $customer->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Customer tax profile belongs to a different scope.');
        }

        $taxGroupId = $this->nullableInt($data, 'tax_group_id');
        if ($taxGroupId !== null) {
            $this->assertGroupInScope(TaxGroup::query()->findOrFail($taxGroupId), $tenantId, $organizationUnitId);
        }

        $profile ??= CustomerTaxProfile::query()->firstOrNew(['customer_id' => $customer->getKey()]);
        $profile->forceFill([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customer->getKey(),
            'tax_group_id' => $taxGroupId,
            'registration_number' => $data['registration_number'] ?? null,
            'exemption_status' => $this->validExemptionStatus((string) ($data['exemption_status'] ?? 'taxable')),
            'active' => (bool) ($data['active'] ?? true),
        ])->save();

        return $profile->refresh()->load(['customer', 'taxGroup']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSupplierProfile(array $data, ?SupplierTaxProfile $profile = null): SupplierTaxProfile
    {
        $tenantId = (int) $data['tenant_id'];
        $organizationUnitId = $this->nullableInt($data, 'organization_unit_id');
        $supplier = Supplier::query()->findOrFail((int) $data['supplier_id']);
        if ((int) $supplier->tenant_id !== $tenantId || $supplier->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Supplier tax profile belongs to a different scope.');
        }

        $taxGroupId = $this->nullableInt($data, 'tax_group_id');
        if ($taxGroupId !== null) {
            $this->assertGroupInScope(TaxGroup::query()->findOrFail($taxGroupId), $tenantId, $organizationUnitId);
        }

        $profile ??= SupplierTaxProfile::query()->firstOrNew(['supplier_id' => $supplier->getKey()]);
        $profile->forceFill([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_id' => $supplier->getKey(),
            'tax_group_id' => $taxGroupId,
            'registration_number' => $data['registration_number'] ?? null,
            'exemption_status' => $this->validExemptionStatus((string) ($data['exemption_status'] ?? 'taxable')),
            'active' => (bool) ($data['active'] ?? true),
        ])->save();

        return $profile->refresh()->load(['supplier', 'taxGroup']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function savePostingProfile(array $data, ?TaxPostingProfile $profile = null): TaxPostingProfile
    {
        $tenantId = (int) $data['tenant_id'];
        $organizationUnitId = $this->nullableInt($data, 'organization_unit_id');
        $tax = Tax::query()->findOrFail((int) $data['tax_id']);
        $this->assertTaxInScope($tax, $tenantId, $organizationUnitId);

        $account = FinanceAccount::query()->findOrFail((int) $data['account_id']);
        if ((int) $account->tenant_id !== $tenantId || $account->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Tax posting profile account belongs to a different scope.');
        }
        if (! (bool) $account->is_active || ! (bool) $account->is_posting_account) {
            throw new InvalidArgumentException('Tax posting profile account must be active and postable.');
        }

        $direction = trim((string) ($data['direction'] ?? 'tax'));
        $duplicate = TaxPostingProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('tax_id', $tax->getKey())
            ->where('direction', $direction)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query): Builder => $query->whereNull('organization_unit_id'),
                fn (Builder $query): Builder => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->when($profile instanceof TaxPostingProfile && $profile->exists, fn (Builder $query): Builder => $query->whereKeyNot($profile->getKey()))
            ->exists();
        if ($duplicate) {
            throw new InvalidArgumentException('Tax posting profile already exists for this tax and direction.');
        }

        $profile ??= new TaxPostingProfile;
        $profile->forceFill([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'tax_id' => $tax->getKey(),
            'direction' => $direction,
            'account_id' => $account->getKey(),
            'posting_key' => $data['posting_key'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ])->save();

        return $profile->refresh()->load(['tax', 'account']);
    }

    private function assertTaxInScope(Tax $tax, int $tenantId, ?int $organizationUnitId): void
    {
        if ((int) $tax->tenant_id !== $tenantId || $tax->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Tax belongs to a different scope.');
        }
    }

    private function assertGroupInScope(TaxGroup $group, int $tenantId, ?int $organizationUnitId): void
    {
        if ((int) $group->tenant_id !== $tenantId || $group->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Tax group belongs to a different scope.');
        }
        if (! (bool) $group->active) {
            throw new InvalidArgumentException('Inactive tax group cannot be used.');
        }
    }

    private function validExemptionStatus(string $status): string
    {
        if (! in_array($status, config('tax.exemption_statuses', []), true)) {
            throw new InvalidArgumentException('Tax profile exemption status is invalid.');
        }

        return $status;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
    }
}
