<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;

final class PaymentSettlementService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function transitionLine(Payment $payment, int $lineId, string $toStatus, ?array $metadata = null): PaymentLine
    {
        return DB::transaction(function () use ($payment, $lineId, $toStatus, $metadata): PaymentLine {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $this->assertPaymentAllowsSettlement($payment);

            $line = PaymentLine::query()
                ->where('payment_id', $payment->getKey())
                ->with('paymentMethod')
                ->lockForUpdate()
                ->findOrFail($lineId);

            $fromStatus = strtolower(trim((string) $line->status));
            $toStatus = strtolower(trim($toStatus));
            if ($fromStatus === $toStatus) {
                return $line;
            }

            $type = $this->methodType($line);
            $transitions = $this->allowedTransitions()[$type] ?? [];
            $allowed = $transitions[$fromStatus] ?? [];
            if (! in_array($toStatus, $allowed, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Payment line status cannot transition from %s to %s for %s payments.',
                    $fromStatus,
                    $toStatus,
                    $type,
                ));
            }

            $line->forceFill([
                'status' => $toStatus,
                'cleared_amount' => $this->clearedAmountForStatus($line, $toStatus),
                ...$this->instrumentDatesForStatus($toStatus),
                'metadata' => array_replace($line->metadata ?? [], $metadata ?? []),
            ])->save();

            $payment->forceFill([
                'instrument_status' => $this->aggregateInstrumentStatus($payment),
            ])->save();

            return $line->refresh();
        });
    }

    private function assertPaymentAllowsSettlement(Payment $payment): void
    {
        $status = $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);

        if (in_array($status, [PaymentStatus::Cancelled, PaymentStatus::Void, PaymentStatus::Reversed, PaymentStatus::Refunded], true)) {
            throw new InvalidArgumentException('Cancelled, void, refunded, or reversed payments cannot be settled.');
        }
    }

    private function methodType(PaymentLine $line): string
    {
        $type = $line->paymentMethod?->method_type;
        $value = $type instanceof PaymentMethodType ? $type->value : (string) $type;

        return match ($value) {
            PaymentMethodType::Cheque->value => 'cheque',
            PaymentMethodType::BankTransfer->value,
            PaymentMethodType::DirectDebit->value => 'bank_transfer',
            PaymentMethodType::Card->value => 'card',
            PaymentMethodType::Cash->value => 'cash',
            PaymentMethodType::DigitalWallet->value,
            PaymentMethodType::MobileWallet->value => 'wallet',
            PaymentMethodType::Other->value => 'other',
            default => 'other',
        };
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
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

    /**
     * @return array<string, mixed>
     */
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
        $statuses = $payment->lines()
            ->pluck('status')
            ->map(fn (mixed $status): string => strtolower((string) $status))
            ->filter()
            ->values()
            ->all();

        foreach ([
            PaymentInstrumentStatus::Bounced->value,
            PaymentInstrumentStatus::Returned->value,
            PaymentInstrumentStatus::Failed->value,
            PaymentInstrumentStatus::Cancelled->value,
            PaymentInstrumentStatus::Deposited->value,
            PaymentInstrumentStatus::Issued->value,
            PaymentInstrumentStatus::Received->value,
            PaymentInstrumentStatus::Initiated->value,
            PaymentInstrumentStatus::Authorized->value,
            PaymentInstrumentStatus::Captured->value,
        ] as $priority) {
            if (in_array($priority, $statuses, true)) {
                return $priority;
            }
        }

        if ($statuses !== [] && count(array_diff($statuses, [
            PaymentInstrumentStatus::Cleared->value,
            PaymentInstrumentStatus::Settled->value,
        ])) === 0) {
            return PaymentInstrumentStatus::Cleared->value;
        }

        return PaymentInstrumentStatus::Pending->value;
    }
}
