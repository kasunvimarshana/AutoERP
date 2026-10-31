<?php

declare(strict_types=1);

namespace Modules\Selling\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Selling\Models\SaleReturn;

final class SaleReturnRefundService
{
    private const IDEMPOTENCY_OPERATION = 'selling.return.refund';

    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly IdempotencyService $idempotency,
        private readonly PaymentCreationService $payments,
        private readonly PaymentDocumentLifecycleService $lifecycle,
        private readonly PaymentPostingService $posting,
    ) {}

    /** @param array<string, mixed> $payload */
    public function refund(array $payload, string $idempotencyKey): Payment
    {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Refund idempotency key is required.');
        }

        return DB::transaction(function () use ($payload, $idempotencyKey): Payment {
            $return = SaleReturn::query()
                ->with(['sale.customer', 'invoice'])
                ->lockForUpdate()
                ->findOrFail((int) $payload['return_id']);
            if ((int) $return->tenant_id !== (int) $payload['tenant_id']
                || $return->organization_unit_id !== $payload['organization_unit_id']) {
                throw new InvalidArgumentException('Return is outside the current organization.');
            }
            if ($return->posted_at === null || $return->sale === null || $return->invoice === null) {
                throw new InvalidArgumentException('Only posted sale returns can be refunded.');
            }
            if ((string) $payload['refund_date'] < $return->return_date->toDateString()) {
                throw new InvalidArgumentException('Refund date cannot be before the return date.');
            }

            $record = $this->idempotency->acquire(
                (int) $return->tenant_id,
                $return->organization_unit_id,
                self::IDEMPOTENCY_OPERATION,
                hash('sha256', $idempotencyKey),
                hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                $idempotencyKey,
                isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null,
            );
            if ($record->status === IdempotencyStatus::Completed) {
                $paymentId = $record->document_ids['payment_id'] ?? null;
                if (! is_numeric($paymentId)) {
                    throw new InvalidArgumentException('Completed refund request has no payment reference.');
                }

                return Payment::query()->where('tenant_id', $return->tenant_id)->findOrFail((int) $paymentId);
            }
            if (! $record->wasRecentlyCreated || $record->status !== IdempotencyStatus::InProgress) {
                throw new InvalidArgumentException('Refund request is already being processed.');
            }

            $refunded = $this->refundedAmount($return);
            $available = $this->math->sub((string) $return->credit_available_amount, $refunded);
            if ($this->math->compare($available, (string) $payload['expected_refundable_amount']) !== 0) {
                throw new InvalidArgumentException('Refundable credit changed. Reload the return before refunding.');
            }
            $amount = $this->math->normalize((string) $payload['amount']);
            if ($this->math->compare($amount, self::ZERO) <= 0 || $this->math->compare($amount, $available) > 0) {
                throw new InvalidArgumentException('Refund amount must be positive and cannot exceed the available return credit.');
            }

            $actorId = isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null;
            $payment = $this->payments->create(new CreatePaymentData(
                tenantId: (int) $return->tenant_id,
                paymentType: PaymentType::Refund,
                direction: PaymentDirection::Outbound,
                paymentDate: (string) $payload['refund_date'],
                organizationUnitId: $return->organization_unit_id,
                partyType: 'customer',
                partyId: (int) $return->sale->customer_id,
                sourceType: PaymentSourceType::SaleReturn->value,
                sourceId: (int) $return->getKey(),
                currencyId: $return->invoice->currency_id,
                exchangeRate: (string) $return->invoice->exchange_rate,
                referenceNumber: $payload['reference_number'] ?? null,
                notes: (string) $payload['reason'],
                createdBy: $actorId,
                lines: [new PaymentLineData(
                    amount: $amount,
                    paymentMethodId: (int) $payload['payment_method_id'],
                    referenceNumber: $payload['reference_number'] ?? null,
                    instrumentDirection: 'issued',
                    externalBankName: $payload['external_bank_name'] ?? null,
                    instrumentNumber: $payload['instrument_number'] ?? null,
                    instrumentDate: $payload['instrument_date'] ?? null,
                )],
                payeeName: $return->sale->customer?->display_name ?: $return->sale->customer?->name,
            ));
            $payment = $this->lifecycle->submit($payment, (int) $payment->row_version, $actorId);
            $payment = $this->lifecycle->approve($payment, (int) $payment->row_version, $actorId);
            $payment = $this->posting->post($payment, (int) $payment->row_version, $actorId);

            $this->idempotency->complete(
                $record,
                ['return_id' => (int) $return->getKey(), 'payment_id' => (int) $payment->getKey()],
                ['return_id' => (int) $return->getKey(), 'payment_id' => (int) $payment->getKey()],
            );

            return $payment;
        }, 3);
    }

    public function refundedAmount(SaleReturn $return): string
    {
        return $this->math->normalize((string) ($return->refunded_amount
            ?? $return->postedRefundPayments()->sum('total_amount')));
    }

    public function refundableAmount(SaleReturn $return): string
    {
        return $this->math->sub((string) $return->credit_available_amount, $this->refundedAmount($return));
    }
}
