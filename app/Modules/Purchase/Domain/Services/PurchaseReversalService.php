<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;

final class PurchaseReversalService
{
    public function cancelInvoice(int $invoiceId): array
    {
        return DB::transaction(function () use ($invoiceId): array {
            $invoice = DB::table('invoices')->lockForUpdate()->find($invoiceId);
            if ($invoice === null) {
                throw new \RuntimeException('Invoice not found.');
            }

            if ($invoice->status === 'cancelled') {
                return (array) $invoice;
            }

            DB::table('invoices')->where('id', $invoiceId)->update([
                'status' => 'cancelled',
                'row_version' => (int) $invoice->row_version + 1,
                'updated_at' => now(),
            ]);

            return (array) DB::table('invoices')->find($invoiceId);
        });
    }

    public function voidPayment(int $paymentId): array
    {
        return DB::transaction(function () use ($paymentId): array {
            $payment = DB::table('payments')->lockForUpdate()->find($paymentId);
            if ($payment === null) {
                throw new \RuntimeException('Payment not found.');
            }

            if ($payment->status === 'voided') {
                return (array) $payment;
            }

            foreach (DB::table('payment_allocations')->where('payment_id', $paymentId)->get() as $allocation) {
                if ($allocation->document_type !== 'INVOICE') {
                    continue;
                }

                $invoice = DB::table('invoices')->lockForUpdate()->find((int) $allocation->document_id);
                if ($invoice === null) {
                    continue;
                }

                $paid = max(0.0, (float) $invoice->paid_amount - (float) $allocation->allocated_amount);
                DB::table('invoices')->where('id', (int) $invoice->id)->update([
                    'paid_amount' => $paid,
                    'balance' => round((float) $invoice->grand_total - $paid, 4),
                    'status' => $paid <= 0 ? 'approved' : 'partially_paid',
                    'row_version' => (int) $invoice->row_version + 1,
                    'updated_at' => now(),
                ]);
            }

            DB::table('payments')->where('id', $paymentId)->update([
                'status' => 'voided',
                'row_version' => (int) $payment->row_version + 1,
                'updated_at' => now(),
            ]);

            return (array) DB::table('payments')->find($paymentId);
        });
    }
}
