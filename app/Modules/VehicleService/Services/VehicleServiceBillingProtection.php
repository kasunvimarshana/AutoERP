<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;
use Modules\VehicleService\Models\VehicleServiceJob;

/** Shared document guard for cancellation and post-reversal billing restoration. */
final class VehicleServiceBillingProtection
{
    /** @return list<string> */
    public function blockers(VehicleServiceJob $job, bool $lockDocuments = false): array
    {
        $invoices = Invoice::query()
            ->where('tenant_id', $job->tenant_id)
            ->whereIn('id', $job->invoiceLinks()->select('invoice_id'))
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value, InvoiceStatus::Reversed->value])
            ->orderBy('id');
        $payments = Payment::query()
            ->where('tenant_id', $job->tenant_id)
            ->whereIn('id', $job->paymentLinks()->select('payment_id'))
            ->where(function ($query): void {
                $query->whereNotIn('document_status', [PaymentDocumentStatus::Voided->value, PaymentDocumentStatus::Reversed->value])
                    ->orWhereIn('posting_status', [PaymentPostingStatus::Posting->value, PaymentPostingStatus::Posted->value]);
            })->orderBy('id');
        if ($lockDocuments) {
            // Locking reads see current committed documents after waiting for the
            // job lock, including on MySQL REPEATABLE READ transactions.
            $invoices->lockForUpdate();
            $payments->lockForUpdate();
        }

        $blockers = [];
        if ($invoices->get(['id'])->isNotEmpty()) {
            $blockers[] = 'Reverse or void the linked invoices before cancelling this job.';
        }
        if ($payments->get(['id'])->isNotEmpty()) {
            $blockers[] = 'Reverse or void the linked payments before cancelling this job.';
        }

        return $blockers;
    }
}
