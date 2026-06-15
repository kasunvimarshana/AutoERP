<?php

declare(strict_types=1);

namespace Modules\Voucher\Services;

use Modules\Finance\Enums\JournalStatus;
use Modules\Payment\Enums\PaymentStatus;

final class VoucherPermissionResolver
{
    /**
     * @return list<string>
     */
    public function forPayment(string $status, bool $hasReversal): array
    {
        $actions = ['view_source', 'print'];

        if (in_array($status, [PaymentStatus::Draft->value, PaymentStatus::PendingApproval->value], true)) {
            $actions[] = 'edit_source';
            $actions[] = 'submit';
        }

        if (in_array($status, [PaymentStatus::Draft->value, PaymentStatus::PendingApproval->value], true)) {
            $actions[] = 'approve';
        }

        if (in_array($status, [PaymentStatus::Approved->value, PaymentStatus::Draft->value], true)) {
            $actions[] = 'post';
        }

        if (! $hasReversal && ! in_array($status, [
            PaymentStatus::Cancelled->value,
            PaymentStatus::Void->value,
            PaymentStatus::Reversed->value,
        ], true)) {
            $actions[] = 'reverse';
        }

        return array_values(array_unique($actions));
    }

    /**
     * @return list<string>
     */
    public function forJournal(string $status, bool $hasReversal): array
    {
        $actions = ['view_source', 'print'];

        if ($status === JournalStatus::Draft->value) {
            $actions[] = 'edit_source';
            $actions[] = 'post';
            $actions[] = 'void';
        }

        if ($status === JournalStatus::Posted->value && ! $hasReversal) {
            $actions[] = 'reverse';
        }

        return $actions;
    }
}
