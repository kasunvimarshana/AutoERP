<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServicePaymentOptionService
{
    public function __construct(
        private readonly PaymentMethodService $methods,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly DecimalMath $math,
    ) {}

    /** @return array{job_version: int, methods: list<array<string, mixed>>, credit_allowed: bool, credit_assessment: array{available: bool, can_keep_on_credit: bool, currency_code: string|null, credit_limit: string, open_exposure: string, remaining_credit: string, warning: string|null}} */
    public function options(VehicleServiceJob $job): array
    {
        $methods = $this->methods
            ->effectiveActiveForDirection(
                (int) $job->tenant_id,
                $job->organization_unit_id,
                PaymentDirection::Inbound,
            )
            ->map(fn (PaymentMethod $method): array => [
                'id' => (int) $method->getKey(),
                'code' => (string) $method->code,
                'name' => (string) $method->name,
                'method_type' => $method->method_type instanceof \BackedEnum
                    ? $method->method_type->value
                    : (string) $method->method_type,
                'requires_reference' => (bool) $method->requires_reference,
                'requires_instrument_details' => (bool) $method->requires_instrument_details,
            ])
            ->all();

        $billToCustomerId = (int) ($job->bill_to_customer_id ?? $job->customer_id);
        $customer = Customer::query()
            ->forTenant((int) $job->tenant_id, $job->organization_unit_id === null ? null : (int) $job->organization_unit_id)
            ->with(['creditProfile', 'defaultCurrency'])
            ->find($billToCustomerId);
        $creditProfile = $customer?->creditProfile;
        $exposureByCurrency = $customer === null ? [] : ($this->invoiceBalances->getOutstandingTotalsForParties(
            (int) $job->tenant_id,
            $job->organization_unit_id === null ? null : (int) $job->organization_unit_id,
            InvoicePartyType::Customer->value,
            [$billToCustomerId],
        )[$billToCustomerId] ?? []);
        $assessment = $this->creditAssessment($customer, $exposureByCurrency);

        return [
            'job_version' => (int) $job->row_version,
            'methods' => $methods,
            'credit_allowed' => (bool) ($creditProfile?->is_active && $creditProfile->credit_allowed),
            'credit_assessment' => $assessment,
        ];
    }

    /**
     * @param  list<array{amount: string, currency_code: string|null}>  $exposureByCurrency
     * @return array{available: bool, can_keep_on_credit: bool, currency_code: string|null, credit_limit: string, open_exposure: string, remaining_credit: string, warning: string|null}
     */
    public function creditAssessmentForConfirmation(VehicleServiceJob $job): array
    {
        return DB::transaction(function () use ($job): array {
            $billToCustomerId = (int) ($job->bill_to_customer_id ?? $job->customer_id);
            $customer = Customer::query()
                ->forTenant((int) $job->tenant_id, $job->organization_unit_id === null ? null : (int) $job->organization_unit_id)
                ->whereKey($billToCustomerId)
                ->lockForUpdate()
                ->with(['creditProfile', 'defaultCurrency'])
                ->first();
            $exposureByCurrency = $customer === null ? [] : ($this->invoiceBalances->getOutstandingTotalsForParties(
                (int) $job->tenant_id,
                $job->organization_unit_id === null ? null : (int) $job->organization_unit_id,
                InvoicePartyType::Customer->value,
                [$billToCustomerId],
            )[$billToCustomerId] ?? []);

            return $this->creditAssessment($customer, $exposureByCurrency);
        }, 3);
    }

    /**
     * @param  list<array{amount: string, currency_code: string|null}>  $exposureByCurrency
     * @return array{available: bool, can_keep_on_credit: bool, currency_code: string|null, credit_limit: string, open_exposure: string, remaining_credit: string, warning: string|null}
     */
    private function creditAssessment(?Customer $customer, array $exposureByCurrency): array
    {
        $profile = $customer?->creditProfile;
        $limit = (string) ($profile?->credit_limit ?? '0.000000');
        $currencyCode = $customer?->defaultCurrency?->code;
        $currencyExposure = array_values(array_filter(
            $exposureByCurrency,
            static fn (array $exposure): bool => $currencyCode === null || $exposure['currency_code'] === $currencyCode,
        ));
        $unmatchedCurrencies = array_values(array_filter(
            $exposureByCurrency,
            static fn (array $exposure): bool => $currencyCode !== null && $exposure['currency_code'] !== $currencyCode,
        ));
        $hasMixedCurrencies = count($unmatchedCurrencies) > 0 || count($currencyExposure) > 1;
        $available = ! $hasMixedCurrencies && $profile !== null && (bool) $profile->is_active && (bool) $profile->credit_allowed;
        $exposure = $available && $currencyExposure !== [] ? (string) $currencyExposure[0]['amount'] : '0.000000';
        $remaining = $this->math->sub($limit, $exposure);
        $overLimit = $this->math->compare($exposure, $limit) > 0;
        $warning = null;

        if ($hasMixedCurrencies) {
            $warning = 'Credit limit cannot be checked because the open invoices do not use a single currency matching the customer credit-limit currency.';
        } elseif ($profile === null || ! (bool) $profile->is_active || ! (bool) $profile->credit_allowed) {
            $warning = 'Credit is not enabled for this customer.';
        } elseif ($overLimit && ! (bool) $profile->allow_over_credit) {
            $warning = 'This customer is over the credit limit. Collect a direct payment before proceeding.';
        } elseif ($overLimit) {
            $warning = 'This customer is over the credit limit, but over-credit is allowed.';
        } elseif ($this->math->compare($limit, '0.000000') > 0
            && $this->math->compare(
                $this->math->mul($exposure, '100.000000'),
                $this->math->mul($limit, (string) ($profile->warning_threshold_percent ?? '80.000000')),
            ) >= 0) {
            $warning = 'This customer is approaching the credit limit.';
        }

        return [
            'available' => $available,
            'can_keep_on_credit' => $available && (! $overLimit || (bool) $profile->allow_over_credit),
            'currency_code' => $currencyCode ?? ($currencyExposure[0]['currency_code'] ?? null),
            'credit_limit' => $limit,
            'open_exposure' => $exposure,
            'remaining_credit' => $remaining,
            'warning' => $warning,
        ];
    }
}
