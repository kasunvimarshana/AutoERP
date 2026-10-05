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
        private readonly PaymentInstrumentStateResolver $instrumentStates,
    ) {}

    public function transitionLine(
        Payment $payment,
        int $lineId,
        string $toStatus,
        string $eventDate,
        int $expectedPaymentVersion,
        int $expectedLineVersion,
        ?int $actorId = null,
        ?string $reason = null,
    ): PaymentLine {
        return DB::transaction(function () use (
            $payment,
            $lineId,
            $toStatus,
            $eventDate,
            $expectedPaymentVersion,
            $expectedLineVersion,
            $actorId,
            $reason,
        ): PaymentLine {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $this->assertPaymentVersion($lockedPayment, $expectedPaymentVersion);
            $this->assertPaymentAllowsSettlement($lockedPayment);
            if ($eventDate < $lockedPayment->payment_date->toDateString()) {
                throw new InvalidArgumentException('Payment settlement event date cannot be before the payment date.');
            }

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
            $allowed = $this->allowedTransitionsForLine($lockedPayment, $line);
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
                ...$this->instrumentDatesForStatus($toStatus, $eventDate),
                'row_version' => (int) $line->row_version + 1,
            ])->save();

            $instrumentAfter = $this->instrumentStates->resolve(
                $lockedPayment->lines()->pluck('status')->all(),
            );
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
                    ['payment_line_id' => (int) $line->getKey(), 'line_state' => $toStatus, 'event_date' => $eventDate],
                );
            }

            return $line->refresh();
        });
    }

    /** @return list<string> */
    public function allowedTransitionsForLine(Payment $payment, PaymentLine $line): array
    {
        if (! $this->paymentAllowsSettlement($payment)) {
            return [];
        }

        $type = $this->methodType($line);
        $fromStatus = strtolower(trim((string) $line->status));

        return ($this->allowedTransitions()[$type] ?? [])[$fromStatus] ?? [];
    }

    private function assertPaymentAllowsSettlement(Payment $payment): void
    {
        if (! $this->paymentAllowsSettlement($payment)) {
            throw new InvalidArgumentException('Only approved and posted payments can be settled.');
        }
    }

    private function paymentAllowsSettlement(Payment $payment): bool
    {
        $document = $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
        $posting = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);

        return $document === PaymentDocumentStatus::Approved && $posting === PaymentPostingStatus::Posted;
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

    /** @return array<string, array<string, list<string>>> */
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

    /** @return array<string, string> */
    private function instrumentDatesForStatus(string $status, string $eventDate): array
    {
        return match ($status) {
            'deposited' => ['deposit_date' => $eventDate],
            'cleared', 'settled' => ['clearing_date' => $eventDate, 'realized_date' => $eventDate],
            'bounced' => ['bounced_date' => $eventDate],
            'returned' => ['returned_date' => $eventDate],
            default => [],
        };
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

        return PaymentInstrumentStatus::tryFrom((string) $payment->instrument_status)
            ?? PaymentInstrumentStatus::Pending;
    }
}
