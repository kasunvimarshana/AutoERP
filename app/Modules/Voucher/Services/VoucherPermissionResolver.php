<?php

declare(strict_types=1);

namespace Modules\Voucher\Services;

use Modules\Finance\Enums\JournalStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;

final class VoucherPermissionResolver
{
    /**
     * @return list<string>
     */
    public function forPayment(string $documentStatus, string $postingStatus, bool $hasReversal): array
    {
        $document = PaymentDocumentStatus::from($documentStatus);
        $posting = PaymentPostingStatus::from($postingStatus);
        $actions = ['view_source', 'print'];

        if (in_array($document, [PaymentDocumentStatus::Draft, PaymentDocumentStatus::Rejected], true)) {
            $actions[] = 'edit_source';
            $actions[] = 'submit';
        }
        if ($document === PaymentDocumentStatus::Submitted) {
            $actions[] = 'approve';
        }
        if ($document === PaymentDocumentStatus::Approved && $posting === PaymentPostingStatus::NotPosted) {
            $actions[] = 'post';
            $actions[] = 'void';
        }
        if (! $hasReversal
            && $document === PaymentDocumentStatus::Approved
            && $posting === PaymentPostingStatus::Posted) {
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
