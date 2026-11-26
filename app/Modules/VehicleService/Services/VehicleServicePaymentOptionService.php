<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
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

    /** @return array{job_version: int, methods: list<array<string, mixed>>, credit_allowed: bool, credit_assessment: array{available: bool, can_keep_on_credit: bool, currency_code: string|null, credit_limit: string|null, open_exposure: string, remaining_credit: string|null, warning: string|null}} */
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
        $assessment = $this->creditAssessment($job, $customer);

        return [
            'job_version' => (int) $job->row_version,
            'methods' => $methods,
            'credit_allowed' => (bool) ($creditProfile?->is_active && $creditProfile->credit_allowed),
            'credit_assessment' => $assessment,
        ];
    }

    /**
     * @return array{available: bool, can_keep_on_credit: bool, currency_code: string|null, credit_limit: string|null, open_exposure: string, remaining_credit: string|null, warning: string|null}
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

            return $this->creditAssessment($job, $customer);
        }, 3);
    }

    /**
     * @return array{available: bool, can_keep_on_credit: bool, currency_code: string|null, credit_limit: string|null, open_exposure: string, remaining_credit: string|null, warning: string|null}
     */
    private function creditAssessment(VehicleServiceJob $job, ?Customer $customer): array
    {
        $profile = $customer?->creditProfile;
        $hasCreditPrivilege = $customer !== null && $profile !== null && (bool) $profile->is_active && (bool) $profile->credit_allowed;
        $hasAssignedLimit = $profile?->credit_limit !== null && $profile->credit_limit !== '';
        $limit = $hasAssignedLimit ? (string) $profile->credit_limit : null;

        $targetCurrencyId = $customer?->defaultCurrency !== null
            ? (int) $customer->defaultCurrency->getKey()
            : null;
        $targetCurrencyCode = $customer?->defaultCurrency?->code;

        $targetExchangeRate = null;
        $jobInvoice = $job->invoiceLinks()
            ->where('status', 'active')
            ->with('invoice')
            ->first()
            ?->invoice;

        if ($jobInvoice !== null
            && $this->math->compare((string) $jobInvoice->exchange_rate, '0.000000') > 0
            && (
                ($targetCurrencyId !== null && $jobInvoice->currency_id !== null && (int) $jobInvoice->currency_id === $targetCurrencyId)
                || ($targetCurrencyCode !== null && $jobInvoice->currency_code_snapshot !== null && $jobInvoice->currency_code_snapshot === $targetCurrencyCode)
            )) {
            $targetExchangeRate = (string) $jobInvoice->exchange_rate;
        }

        $billToCustomerId = (int) ($job->bill_to_customer_id ?? $job->customer_id);
        $exposureResult = $customer === null
            ? [
                'currency_code' => $targetCurrencyCode,
                'currency_id' => $targetCurrencyId,
                'total_exposure' => '0.000000',
                'total_base_exposure' => '0.000000',
                'unconverted_currencies' => [],
            ]
            : $this->invoiceBalances->getPartyExposureInCurrency(
                (int) $job->tenant_id,
                $job->organization_unit_id === null ? null : (int) $job->organization_unit_id,
                InvoicePartyType::Customer->value,
                $billToCustomerId,
                $targetCurrencyId,
                $targetCurrencyCode,
                $targetExchangeRate,
            );

        $exposure = $exposureResult['total_exposure'];
        $currencyCode = $exposureResult['currency_code'] ?? $targetCurrencyCode;
        $unconverted = $exposureResult['unconverted_currencies'];
        $available = $unconverted === [] && $hasCreditPrivilege;
        $warning = null;
        $overLimit = false;
        $remaining = null;

        if ($unconverted !== []) {
            $warning = 'Credit limit cannot be assessed accurately because exchange rates are missing for currencies: '.implode(', ', $unconverted).'.';
        } elseif (! $hasCreditPrivilege) {
            $warning = 'Credit is not enabled for this customer.';
        } elseif ($hasAssignedLimit) {
            $remaining = $this->math->sub($limit, $exposure);
            $overLimit = $this->math->compare($exposure, $limit) > 0;

            if ($overLimit && ! (bool) $profile->allow_over_credit) {
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
        }

        return [
            'available' => $available,
            'can_keep_on_credit' => $available && (! $overLimit || (bool) ($profile?->allow_over_credit ?? false)),
            'currency_code' => $currencyCode,
            'credit_limit' => $limit,
            'open_exposure' => $exposure,
            'remaining_credit' => $remaining,
            'warning' => $warning,
        ];
    }
}
