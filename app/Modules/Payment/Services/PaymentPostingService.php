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
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentPostingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $postings,
        private readonly PaymentValidationService $validator,
        private readonly PaymentLifecycleEventRecorder $events,
        private readonly PaymentAllocationService $allocations,
    ) {}

    public function post(Payment $payment, int $expectedVersion, ?int $postedBy = null): Payment
    {
        return DB::transaction(function () use ($payment, $expectedVersion, $postedBy): Payment {
            $locked = Payment::query()
                ->with(['lines', 'allocations'])
                ->lockForUpdate()
                ->findOrFail($payment->getKey());
            $this->assertVersion($locked, $expectedVersion);

            $postingStatus = $this->postingStatus($locked);
            if ($postingStatus === PaymentPostingStatus::Posted && $locked->finance_posting_reference !== null) {
                return $locked->refresh()->load(['lines', 'allocations', 'unappliedBalance', 'lifecycleEvents']);
            }
            if ($this->documentStatus($locked) !== PaymentDocumentStatus::Approved) {
                throw new InvalidArgumentException('Only approved payment documents can be posted.');
            }
            if ($postingStatus !== PaymentPostingStatus::NotPosted && $postingStatus !== PaymentPostingStatus::Failed) {
                throw new InvalidArgumentException('Payment posting is already in progress or complete.');
            }
            if ($locked->lines->isEmpty()) {
                throw new InvalidArgumentException('Payment posting requires at least one payment line.');
            }
            $this->validator->assertPositive((string) $locked->total_amount, 'Payment total');
            foreach ($locked->lines as $line) {
                if (! $line instanceof PaymentLine) {
                    throw new InvalidArgumentException('Payment line data is invalid.');
                }
                $this->validator->assertPositive((string) $line->amount, 'Payment line amount');
            }

            $locked->forceFill([
                'posting_status' => PaymentPostingStatus::Posting->value,
                'row_version' => (int) $locked->row_version + 1,
            ])->save();
            $locked = $locked->refresh()->load('lines');
            $this->events->record(
                $locked,
                PaymentLifecycleDimension::Posting,
                $postingStatus,
                PaymentPostingStatus::Posting,
                $postedBy,
                'Payment posting started.',
            );

            $result = $this->postings->post($this->postingContext($locked), $postedBy);
            $locked->forceFill([
                'finance_posting_reference' => $result->journalNumber,
                'posting_correlation_key' => 'payment:'.$locked->getKey().':post',
                'posting_status' => PaymentPostingStatus::Posted->value,
                'posted_by' => $postedBy,
                'posted_at' => now(),
                'row_version' => (int) $locked->row_version + 1,
            ])->save();
            $locked = $locked->refresh();
            $this->events->record(
                $locked,
                PaymentLifecycleDimension::Posting,
                PaymentPostingStatus::Posting,
                PaymentPostingStatus::Posted,
                $postedBy,
                'Payment posted to Finance as '.$result->journalNumber.'.',
            );

            $locked = $this->allocations->realizePending($locked, $postedBy);

            return $locked->refresh()->load(['lines', 'allocations', 'unappliedBalance', 'lifecycleEvents']);
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
                $lines[] = $this->cashPostingLine($payment, $line, (string) $line->amount, '0.000000');
            }
            $lines[] = new PostingLine(
                debit: '0.000000',
                credit: (string) $payment->total_amount,
                description: 'Payment receipt '.$payment->payment_number,
                profileKey: 'receivable',
                sourceLineType: 'payment',
                sourceLineId: (int) $payment->getKey(),
            );

            return $lines;
        }

        $lines[] = new PostingLine(
            debit: (string) $payment->total_amount,
            credit: '0.000000',
            description: 'Payment disbursement '.$payment->payment_number,
            profileKey: 'payable',
            sourceLineType: 'payment',
            sourceLineId: (int) $payment->getKey(),
        );
        foreach ($payment->lines as $line) {
            $lines[] = $this->cashPostingLine($payment, $line, '0.000000', (string) $line->amount);
        }

        return $lines;
    }

    private function cashPostingLine(Payment $payment, PaymentLine $line, string $debit, string $credit): PostingLine
    {
        return new PostingLine(
            debit: $debit,
            credit: $credit,
            description: 'Payment '.$payment->payment_number,
            profileKey: $this->cashProfileKey($line),
            sourceLineType: 'payment_line',
            sourceLineId: (int) $line->getKey(),
            dimensions: [
                'payment_method_id' => (string) $line->payment_method_id,
                'payment_method_code' => (string) $line->payment_method_code_snapshot,
            ],
        );
    }

    private function profileCode(Payment $payment): string
    {
        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);

        return $direction === PaymentDirection::Inbound ? 'payment_received' : 'payment_made';
    }

    private function cashProfileKey(PaymentLine $line): string
    {
        return (string) $line->payment_method_type_snapshot === PaymentMethodType::Cash->value ? 'cash' : 'bank';
    }

    private function assertVersion(Payment $payment, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $payment->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Payment was changed by another request. Reload it before posting.');
        }
    }

    private function documentStatus(Payment $payment): PaymentDocumentStatus
    {
        return $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
    }

    private function postingStatus(Payment $payment): PaymentPostingStatus
    {
        return $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
    }
}
