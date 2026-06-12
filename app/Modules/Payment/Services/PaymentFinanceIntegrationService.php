<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Payment\DTOs\PaymentPostingRequest;
use Modules\Payment\Models\Payment;

final class PaymentFinanceIntegrationService
{
    public function __construct(private readonly FinancePostingInterface $financePostings) {}

    /**
     * @param  list<FinancePostingLine>  $lines
     */
    public function preparePaymentPostingRequest(
        int $paymentId,
        array $lines,
        ?string $postingProfileCode = null,
    ): PaymentPostingRequest
    {
        $payment = Payment::query()->findOrFail($paymentId);

        return new PaymentPostingRequest(
            paymentId: (int) $payment->getKey(),
            paymentType: $payment->payment_type instanceof \BackedEnum
                ? (string) $payment->payment_type->value
                : (string) $payment->payment_type,
            paymentDate: $payment->payment_date->toDateString(),
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $lines,
            postingProfileCode: $postingProfileCode,
        );
    }

    public function toFinancePostingRequest(PaymentPostingRequest $request): FinancePostingRequest
    {
        $payment = Payment::query()->findOrFail($request->paymentId);

        return new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'payment',
                sourceId: (int) $payment->getKey(),
                tenantId: (int) $payment->tenant_id,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: 'payment',
                sourceNumber: (string) $payment->payment_number,
                sourceDate: $payment->payment_date->toDateString(),
            ),
            postingDate: $request->paymentDate,
            currencyId: $request->currencyId,
            exchangeRate: $request->exchangeRate,
            lines: $request->lines,
            description: 'Payment posting '.$payment->payment_number,
            postingProfileCode: $request->postingProfileCode,
        );
    }

    public function validatePostingRequest(FinancePostingRequest $request): void
    {
        $this->financePostings->validatePosting($request);
    }
}
