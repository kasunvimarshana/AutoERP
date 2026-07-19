<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\DTOs\RentalRateLineData;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

class StoreRentalAgreementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(RentalAgreementKind::class)],
            'customer_id' => ['nullable', 'integer', $this->tenantExists('customers')],
            'supplier_id' => ['nullable', 'integer', $this->tenantExists('suppliers')],
            'agreement_number' => ['nullable', 'string', 'max:100'],
            'executed_at' => ['nullable', 'date_format:Y-m-d'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'billing_basis' => ['required', Rule::enum(RentalBillingBasis::class)],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'tax_group_id' => ['nullable', 'integer', $this->tenantExists('tax_groups')],
            'included_km' => ['nullable', 'numeric', 'min:0'],
            'deposit_required' => ['nullable', 'boolean'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.code' => ['required', Rule::enum(RentalRateCode::class), 'distinct'],
            'rates.*.unit' => ['required', Rule::enum(RentalRateUnit::class)],
            'rates.*.rate' => ['required', 'numeric', 'min:0'],
            'rates.*.is_taxable' => ['required', 'boolean'],
            'rates.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toData(): RentalAgreementData
    {
        $rates = [];
        foreach ($this->validated('rates') as $rate) {
            $rates[] = new RentalRateLineData(
                code: RentalRateCode::from((string) $rate['code']),
                unit: RentalRateUnit::from((string) $rate['unit']),
                rate: (string) $rate['rate'],
                isTaxable: (bool) $rate['is_taxable'],
                description: isset($rate['description']) ? trim((string) $rate['description']) ?: null : null,
            );
        }

        return new RentalAgreementData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            kind: RentalAgreementKind::from((string) $this->validated('kind')),
            customerId: $this->integerOrNull('customer_id'),
            supplierId: $this->integerOrNull('supplier_id'),
            agreementNumber: $this->nullableString('agreement_number'),
            executedAt: $this->nullableString('executed_at'),
            startsOn: (string) $this->validated('starts_on'),
            endsOn: $this->nullableString('ends_on'),
            billingBasis: RentalBillingBasis::from((string) $this->validated('billing_basis')),
            currencyId: (int) $this->validated('currency_id'),
            taxGroupId: $this->integerOrNull('tax_group_id'),
            includedKm: (string) ($this->validated('included_km') ?? '0.000000'),
            depositRequired: (bool) ($this->validated('deposit_required') ?? false),
            depositAmount: (string) ($this->validated('deposit_amount') ?? '0.000000'),
            paymentTermsDays: (int) ($this->validated('payment_terms_days') ?? 0),
            terms: $this->nullableString('terms'),
            notes: $this->nullableString('notes'),
            rates: $rates,
            actorId: $this->currentUserId(),
        );
    }

    private function integerOrNull(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
