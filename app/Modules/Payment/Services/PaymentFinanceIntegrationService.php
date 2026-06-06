<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\DTOs\Integration\FinancePostingRequest;
use Modules\Core\DTOs\Integration\PostingLineData;
use Modules\Core\DTOs\Integration\PostingSourceData;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Payment\DTOs\PaymentPostingRequest;
use Modules\Payment\Models\Payment;

final class PaymentFinanceIntegrationService
{
    public function __construct(private readonly FinancePostingInterface $financePostings) {}

    /**
     * @param  list<PostingLineData>  $lines
     */
    public function preparePaymentPostingRequest(int $paymentId, array $lines): PaymentPostingRequest
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
            ),
            postingDate: $request->paymentDate,
            currencyId: $request->currencyId,
            exchangeRate: $request->exchangeRate,
            lines: $request->lines,
            description: 'Payment posting '.$payment->payment_number,
        );
    }

    public function validatePostingRequest(FinancePostingRequest $request): void
    {
        $this->financePostings->validatePosting($request);
    }
}
