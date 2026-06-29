<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\Enums\PaymentInstrumentStatus;

final class PaymentInstrumentStateResolver
{
    /**
     * @param  iterable<string|\BackedEnum>  $statuses
     */
    public function resolve(iterable $statuses): PaymentInstrumentStatus
    {
        $values = [];
        foreach ($statuses as $status) {
            $value = $status instanceof \BackedEnum ? (string) $status->value : strtolower(trim((string) $status));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        if ($values === []) {
            return PaymentInstrumentStatus::Pending;
        }

        foreach ([
            PaymentInstrumentStatus::Refunded => [PaymentInstrumentStatus::Refunded->value],
            PaymentInstrumentStatus::Reversed => [PaymentInstrumentStatus::Reversed->value],
            PaymentInstrumentStatus::Settled => [PaymentInstrumentStatus::Settled->value],
            PaymentInstrumentStatus::Cleared => [
                PaymentInstrumentStatus::Cleared->value,
                PaymentInstrumentStatus::Settled->value,
            ],
        ] as $aggregate => $finalStates) {
            if (array_diff($values, $finalStates) === []) {
                return $aggregate;
            }
        }

        foreach ([
            PaymentInstrumentStatus::Bounced,
            PaymentInstrumentStatus::Returned,
            PaymentInstrumentStatus::Failed,
            PaymentInstrumentStatus::Cancelled,
            PaymentInstrumentStatus::Refunded,
            PaymentInstrumentStatus::Reversed,
            PaymentInstrumentStatus::Deposited,
            PaymentInstrumentStatus::Issued,
            PaymentInstrumentStatus::Received,
            PaymentInstrumentStatus::Initiated,
            PaymentInstrumentStatus::Authorized,
            PaymentInstrumentStatus::Captured,
            PaymentInstrumentStatus::Settled,
            PaymentInstrumentStatus::Cleared,
        ] as $priority) {
            if (in_array($priority->value, $values, true)) {
                return $priority;
            }
        }

        return PaymentInstrumentStatus::Pending;
    }
}
