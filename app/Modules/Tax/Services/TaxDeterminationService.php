<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Tax\Contracts\TaxItemContextProviderInterface;
use Modules\Tax\Data\TaxItemContext;
use Modules\Tax\DTOs\ApplicableTaxData;
use Modules\Tax\DTOs\TaxDeterminationContext;
use Modules\Tax\DTOs\TaxDeterminationResult;
use Modules\Tax\Models\CustomerTaxProfile;
use Modules\Tax\Models\SupplierTaxProfile;
use Modules\Tax\Models\Tax;
use Modules\Tax\Models\TaxGroup;
use Modules\Tax\Models\TaxRate;

final class TaxDeterminationService
{
    public function __construct(
        private readonly TaxItemContextProviderInterface $items,
    ) {}

    public function determine(TaxDeterminationContext $context): TaxDeterminationResult
    {
        $item = $context->itemId === null
            ? null
            : $this->items->find($context->tenantId, $context->organizationUnitId, $context->itemId);
        if ($item instanceof TaxItemContext) {
            if ($item->isTaxExempt) {
                return new TaxDeterminationResult(null, [], 'exempt');
            }
        }

        $profileStatus = 'taxable';
        $profileTaxGroupId = null;

        if ($context->customerId !== null) {
            $profile = $this->profileQuery(CustomerTaxProfile::query(), $context)
                ->where('customer_id', $context->customerId)
                ->where('active', true)
                ->first();
            if ($profile instanceof CustomerTaxProfile) {
                $profileStatus = (string) $profile->exemption_status;
                $profileTaxGroupId = $profile->tax_group_id !== null ? (int) $profile->tax_group_id : null;
            }
        }

        if ($context->supplierId !== null) {
            $profile = $this->profileQuery(SupplierTaxProfile::query(), $context)
                ->where('supplier_id', $context->supplierId)
                ->where('active', true)
                ->first();
            if ($profile instanceof SupplierTaxProfile) {
                $profileStatus = (string) $profile->exemption_status;
                $profileTaxGroupId = $profile->tax_group_id !== null ? (int) $profile->tax_group_id : null;
            }
        }

        if (in_array($profileStatus, ['exempt', 'suspended'], true)) {
            return new TaxDeterminationResult(null, [], $profileStatus);
        }

        $taxGroupId = $this->itemTaxGroupId($item, $context)
            ?? $profileTaxGroupId
            ?? $context->documentTaxGroupId
            ?? $this->defaultGroupId($context);

        if ($taxGroupId === null) {
            return new TaxDeterminationResult(null, [], $profileStatus);
        }

        $group = TaxGroup::query()->with('lines.tax')->findOrFail($taxGroupId);
        $this->assertGroupInScope($group, $context);

        $taxes = [];
        foreach ($group->lines->where('active', true)->sortBy('sequence') as $line) {
            $tax = $line->tax;
            if (! $tax instanceof Tax || ! (bool) $tax->active) {
                throw new InvalidArgumentException('Tax group contains an inactive or missing tax.');
            }

            $rate = $profileStatus === 'zero-rated'
                ? '0.000000'
                : $this->rateForDate($tax, $context->documentDate);

            $taxes[] = new ApplicableTaxData(
                taxId: (int) $tax->getKey(),
                taxCode: (string) $tax->code,
                taxName: (string) $tax->name,
                taxType: (string) $tax->tax_type,
                calculationMethod: (string) $tax->calculation_method,
                rate: $rate,
                sequence: (int) $line->sequence,
                isWithholding: (bool) $tax->is_withholding,
                recoverable: (bool) $tax->recoverable,
                payable: (bool) $tax->payable,
                receivable: (bool) $tax->receivable,
                taxGroupId: (int) $group->getKey(),
            );
        }

        return new TaxDeterminationResult((int) $group->getKey(), $taxes, $profileStatus);
    }

    private function rateForDate(Tax $tax, string $date): string
    {
        $rate = TaxRate::query()
            ->where('tenant_id', (int) $tax->tenant_id)
            ->where('tax_id', $tax->getKey())
            ->where('active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('effective_to')
                ->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first();

        if (! $rate instanceof TaxRate) {
            throw new InvalidArgumentException("Active tax rate is missing for tax [{$tax->code}] on {$date}.");
        }

        return (string) $rate->rate;
    }

    private function itemTaxGroupId(?TaxItemContext $item, TaxDeterminationContext $context): ?int
    {
        if (! $item instanceof TaxItemContext) {
            return null;
        }

        $groupId = null;
        if ($this->isPurchaseDocument($context->documentType)) {
            $groupId = $item->purchaseTaxGroupId;
        }
        if ($this->isSalesDocument($context->documentType)) {
            $groupId = $item->salesTaxGroupId;
        }

        $groupId ??= $item->defaultTaxGroupId;

        return $groupId !== null ? (int) $groupId : null;
    }

    private function defaultGroupId(TaxDeterminationContext $context): ?int
    {
        $group = TaxGroup::query()
            ->where('tenant_id', $context->tenantId)
            ->where('is_default', true)
            ->where('active', true);

        $this->scopeOrganization($group, $context->organizationUnitId);

        return $group->value('id') !== null ? (int) $group->value('id') : null;
    }

    private function assertGroupInScope(TaxGroup $group, TaxDeterminationContext $context): void
    {
        if ((int) $group->tenant_id !== $context->tenantId || $group->organization_unit_id !== $context->organizationUnitId) {
            throw new InvalidArgumentException('Tax group belongs to a different scope.');
        }
        if (! (bool) $group->active) {
            throw new InvalidArgumentException('Inactive tax group cannot be used.');
        }
    }

    private function profileQuery(Builder $query, TaxDeterminationContext $context): Builder
    {
        $query->where('tenant_id', $context->tenantId);
        $this->scopeOrganization($query, $context->organizationUnitId);

        return $query;
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    private function isPurchaseDocument(string $documentType): bool
    {
        return str_contains($documentType, 'purchase')
            || str_contains($documentType, 'supplier')
            || str_contains($documentType, 'inbound');
    }

    private function isSalesDocument(string $documentType): bool
    {
        return str_contains($documentType, 'sales')
            || str_contains($documentType, 'customer')
            || str_contains($documentType, 'outbound')
            || str_contains($documentType, 'service');
    }
}
