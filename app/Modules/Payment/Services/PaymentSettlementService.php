<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;

final class PaymentSettlementService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentLifecycleEventRecorder $events,
    ) {}

    public function transitionLine(
        Payment $payment,
        int $lineId,
        string $toStatus,
        int $expectedPaymentVersion,
        int $expectedLineVersion,
        ?int $actorId = null,
        ?string $reason = null,
    ): PaymentLine {
        return DB::transaction(function () use (
            $payment,
            $lineId,
            $toStatus,
            $expectedPaymentVersion,
            $expectedLineVersion,
            $actorId,
            $reason,
        ): PaymentLine {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $this->assertPaymentVersion($lockedPayment, $expectedPaymentVersion);
            $this->assertPaymentAllowsSettlement($lockedPayment);

            $line = PaymentLine::query()
                ->where('payment_id', $lockedPayment->getKey())
                ->lockForUpdate()
                ->findOrFail($lineId);
            if ((int) $line->row_version !== $expectedLineVersion) {
                throw new InvalidArgumentException('Payment line was changed by another request. Reload it before settling.');
            }

            $fromStatus = strtolower(trim((string) $line->status));
            $toStatus = strtolower(trim($toStatus));
            if ($fromStatus === $toStatus) {
                return $line;
            }
            $type = $this->methodType($line);
            $allowed = ($this->allowedTransitions()[$type] ?? [])[$fromStatus] ?? [];
            if (! in_array($toStatus, $allowed, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Payment line status cannot transition from %s to %s for %s payments.',
                    $fromStatus,
                    $toStatus,
                    $type,
                ));
            }

            $instrumentBefore = $this->instrumentStatus($lockedPayment);
            $line->forceFill([
                'status' => $toStatus,
                'cleared_amount' => $this->clearedAmountForStatus($line, $toStatus),
                ...$this->instrumentDatesForStatus($toStatus),
                'row_version' => (int) $line->row_version + 1,
            ])->save();

            $instrumentAfter = PaymentInstrumentStatus::from($this->aggregateInstrumentStatus($lockedPayment));
            $lockedPayment->forceFill([
                'instrument_status' => $instrumentAfter->value,
                'row_version' => (int) $lockedPayment->row_version + 1,
            ])->save();
            $lockedPayment = $lockedPayment->refresh();
            if ($instrumentBefore !== $instrumentAfter) {
                $this->events->record(
                    $lockedPayment,
                    PaymentLifecycleDimension::Instrument,
                    $instrumentBefore,
                    $instrumentAfter,
                    $actorId,
                    $reason,
                    ['payment_line_id' => (int) $line->getKey(), 'line_state' => $toStatus],
                );
            }

            return $line->refresh();
        });
    }

    private function assertPaymentAllowsSettlement(Payment $payment): void
    {
        $document = $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
        $posting = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
        if ($document !== PaymentDocumentStatus::Approved || $posting !== PaymentPostingStatus::Posted) {
            throw new InvalidArgumentException('Only approved and posted payments can be settled.');
        }
    }

    private function methodType(PaymentLine $line): string
    {
        return match ((string) $line->payment_method_type_snapshot) {
            PaymentMethodType::Cheque->value => 'cheque',
            PaymentMethodType::BankTransfer->value,
            PaymentMethodType::DirectDebit->value => 'bank_transfer',
            PaymentMethodType::Card->value => 'card',
            PaymentMethodType::Cash->value => 'cash',
            PaymentMethodType::DigitalWallet->value,
            PaymentMethodType::MobileWallet->value => 'wallet',
            default => 'other',
        };
    }

    private function allowedTransitions(): array
    {
        $cashLike = [
            'pending' => ['cleared', 'cancelled', 'reversed'],
            'cleared' => ['reversed'],
            'cancelled' => [],
            'reversed' => [],
        ];

        return [
            'cash' => $cashLike,
            'wallet' => [
                'pending' => ['authorized', 'settled', 'failed', 'cancelled', 'reversed'],
                'authorized' => ['settled', 'failed', 'cancelled', 'reversed'],
                'settled' => ['refunded', 'reversed'],
                'failed' => ['authorized', 'cancelled'],
                'refunded' => [],
                'cancelled' => [],
                'reversed' => [],
            ],
            'other' => $cashLike,
            'cheque' => [
                'pending' => ['issued', 'received', 'cancelled', 'reversed'],
                'issued' => ['deposited', 'cancelled', 'reversed'],
                'received' => ['deposited', 'cancelled', 'reversed'],
                'deposited' => ['cleared', 'bounced', 'cancelled', 'reversed'],
                'bounced' => ['deposited', 'cancelled', 'reversed'],
                'cleared' => ['reversed'],
                'cancelled' => [],
                'reversed' => [],
            ],
            'bank_transfer' => [
                'pending' => ['initiated', 'settled', 'failed', 'cancelled', 'reversed'],
                'initiated' => ['settled', 'failed', 'cancelled', 'reversed'],
                'failed' => ['initiated', 'cancelled'],
                'settled' => ['reversed'],
                'cancelled' => [],
                'reversed' => [],
            ],
            'card' => [
                'pending' => ['authorized', 'captured', 'settled', 'failed', 'cancelled', 'reversed'],
                'authorized' => ['captured', 'settled', 'failed', 'cancelled', 'reversed'],
                'captured' => ['settled', 'refunded', 'reversed'],
                'settled' => ['refunded', 'reversed'],
                'failed' => ['authorized', 'cancelled'],
                'refunded' => [],
                'cancelled' => [],
                'reversed' => [],
            ],
        ];
    }

    private function clearedAmountForStatus(PaymentLine $line, string $status): string
    {
        if (in_array($status, ['cleared', 'settled', 'captured'], true)) {
            return $this->math->normalize((string) $line->amount);
        }
        if (in_array($status, ['bounced', 'failed', 'cancelled', 'reversed'], true)) {
            return '0.000000';
        }

        return $this->math->normalize((string) $line->cleared_amount);
    }

    private function instrumentDatesForStatus(string $status): array
    {
        $now = now()->toDateString();

        return match ($status) {
            'deposited' => ['deposit_date' => $now],
            'cleared', 'settled' => ['clearing_date' => $now, 'realized_date' => $now],
            'bounced' => ['bounced_date' => $now],
            'returned' => ['returned_date' => $now],
            default => [],
        };
    }

    private function aggregateInstrumentStatus(Payment $payment): string
    {
        $statuses = $payment->lines()->pluck('status')
            ->map(fn (mixed $status): string => strtolower((string) $status))
            ->filter()
            ->values()
            ->all();

        if ($statuses === []) {
            return PaymentInstrumentStatus::Pending->value;
        }

        foreach ([
            PaymentInstrumentStatus::Refunded->value => [PaymentInstrumentStatus::Refunded->value],
            PaymentInstrumentStatus::Reversed->value => [PaymentInstrumentStatus::Reversed->value],
            PaymentInstrumentStatus::Settled->value => [PaymentInstrumentStatus::Settled->value],
            PaymentInstrumentStatus::Cleared->value => [
                PaymentInstrumentStatus::Cleared->value,
                PaymentInstrumentStatus::Settled->value,
            ],
        ] as $aggregate => $finalStates) {
            if (array_diff($statuses, $finalStates) === []) {
                return $aggregate;
            }
        }

        foreach ([
            PaymentInstrumentStatus::Bounced->value,
            PaymentInstrumentStatus::Returned->value,
            PaymentInstrumentStatus::Failed->value,
            PaymentInstrumentStatus::Cancelled->value,
            PaymentInstrumentStatus::Refunded->value,
            PaymentInstrumentStatus::Reversed->value,
            PaymentInstrumentStatus::Deposited->value,
            PaymentInstrumentStatus::Issued->value,
            PaymentInstrumentStatus::Received->value,
            PaymentInstrumentStatus::Initiated->value,
            PaymentInstrumentStatus::Authorized->value,
            PaymentInstrumentStatus::Captured->value,
            PaymentInstrumentStatus::Settled->value,
            PaymentInstrumentStatus::Cleared->value,
        ] as $priority) {
            if (in_array($priority, $statuses, true)) {
                return $priority;
            }
        }

        return PaymentInstrumentStatus::Pending->value;
    }

    private function assertPaymentVersion(Payment $payment, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $payment->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Payment was changed by another request. Reload it before settling.');
        }
    }

    private function instrumentStatus(Payment $payment): PaymentInstrumentStatus
    {
        if ($payment->instrument_status instanceof PaymentInstrumentStatus) {
            return $payment->instrument_status;
        }

        return PaymentInstrumentStatus::tryFrom((string) $payment->instrument_status) ?? PaymentInstrumentStatus::Pending;
    }
}
