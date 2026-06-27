<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentPostingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $postings,
        private readonly PaymentValidationService $validator,
        private readonly PaymentStatusService $statuses,
        private readonly PaymentAllocationService $allocations,
    ) {}

    public function post(Payment $payment, ?int $postedBy = null): Payment
    {
        return DB::transaction(function () use ($payment, $postedBy): Payment {
            $locked = Payment::query()
                ->with(['lines.paymentMethod', 'lines.internalBankAccount', 'bankAccount', 'allocations'])
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if ($locked->finance_journal_entry_id !== null || $locked->posting_status === PaymentPostingStatus::Posted) {
                return $locked->refresh()->load(['lines.paymentMethod', 'allocations', 'unappliedBalance', 'financeJournalEntry']);
            }

            $status = $locked->status instanceof PaymentStatus
                ? $locked->status
                : PaymentStatus::from((string) $locked->status);
            if ($status !== PaymentStatus::Approved) {
                throw new InvalidArgumentException('Only approved payments can be posted.');
            }
            if ($locked->lines->isEmpty()) {
                throw new InvalidArgumentException('Payment posting requires at least one payment line.');
            }
            $this->validator->assertPositive((string) $locked->total_amount, 'Payment total');

            foreach ($locked->lines as $line) {
                if (! $line instanceof PaymentLine || $line->paymentMethod === null) {
                    throw new InvalidArgumentException('Every posted payment line requires a payment method.');
                }
                $this->validator->assertPositive((string) $line->amount, 'Payment line amount');
            }

            $locked->forceFill(['posting_status' => PaymentPostingStatus::Posting->value])->save();

            $result = $this->postings->post($this->postingContext($locked), $postedBy);

            $previous = $status;
            $locked->forceFill([
                'finance_journal_entry_id' => $result->journalId,
                'posting_correlation_key' => 'payment:'.$locked->getKey().':post',
                'posting_status' => PaymentPostingStatus::Posted->value,
                'document_status' => PaymentDocumentStatus::Approved->value,
                'status' => PaymentStatus::Posted->value,
                'posted_at' => now(),
            ])->save();
            $this->statuses->record($locked->refresh(), $previous, PaymentStatus::Posted, $postedBy, 'Payment posted to Finance journal '.$result->journalNumber.'.');

            $locked = $this->allocations->realizePending($locked->refresh(), $postedBy);

            return $locked->refresh()->load(['lines.paymentMethod', 'allocations', 'unappliedBalance', 'financeJournalEntry']);
        });
    }

    private function postingContext(Payment $payment): PostingContext
    {
        return new PostingContext(
            source: new PostingSourceData(
                sourceType: 'payment',
                sourceId: (int) $payment->getKey(),
                tenantId: (int) $payment->tenant_id,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: 'payment',
                sourceNumber: (string) $payment->payment_number,
                sourceDate: $payment->payment_date?->toDateString(),
            ),
            postingDate: $payment->payment_date?->toDateString() ?? now()->toDateString(),
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $this->postingLines($payment),
            description: 'Payment '.$payment->payment_number,
            postingProfileCode: $this->profileCode($payment),
        );
    }

    /** @return list<PostingLine> */
    private function postingLines(Payment $payment): array
    {
        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);

        $lines = [];
        if ($direction === PaymentDirection::Inbound) {
            foreach ($payment->lines as $line) {
                $lines[] = new PostingLine(
                    profileKey: 'settlement',
                    debit: (string) $line->amount,
                    description: 'Payment receipt '.$payment->payment_number,
                    sourceLineType: 'payment_line',
                    sourceLineId: (int) $line->getKey(),
                    contextType: 'payment_method',
                    contextId: (int) $line->payment_method_id,
                );
            }
            $lines[] = new PostingLine(
                profileKey: 'receivable',
                credit: (string) $payment->total_amount,
                description: 'Payment receipt '.$payment->payment_number,
                sourceLineType: 'payment',
                sourceLineId: (int) $payment->getKey(),
            );

            return $lines;
        }

        $lines[] = new PostingLine(
            profileKey: 'payable',
            debit: (string) $payment->total_amount,
            description: 'Payment disbursement '.$payment->payment_number,
            sourceLineType: 'payment',
            sourceLineId: (int) $payment->getKey(),
        );
        foreach ($payment->lines as $line) {
            $lines[] = new PostingLine(
                profileKey: 'settlement',
                credit: (string) $line->amount,
                description: 'Payment disbursement '.$payment->payment_number,
                sourceLineType: 'payment_line',
                sourceLineId: (int) $line->getKey(),
                contextType: 'payment_method',
                contextId: (int) $line->payment_method_id,
            );
        }

        return $lines;
    }

    private function profileCode(Payment $payment): string
    {
        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);

        return $direction === PaymentDirection::Inbound ? 'payment_received' : 'payment_made';
    }

}
