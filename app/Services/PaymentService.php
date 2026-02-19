<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::where('tenant_id', $tenantId);

        if (isset($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function record(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $data['status'] ??= PaymentStatus::Completed;
            $data['payment_number'] ??= 'PAY-'.strtoupper(Str::random(8));
            $data['paid_at'] ??= now();
            $data['recorded_by'] = Auth::id();

            $amount = (string) $data['amount'];
            $fee = (string) ($data['fee_amount'] ?? '0');
            $data['net_amount'] = bcsub($amount, $fee, 8);

            $payment = Payment::create($data);

            // Update invoice if linked
            if (! empty($data['invoice_id'])) {
                $this->applyPaymentToInvoice($data['invoice_id'], $amount);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Payment::class,
                auditableId: $payment->id,
                newValues: ['amount' => $amount, 'payment_number' => $payment->payment_number]
            );

            return $payment->fresh();
        });
    }

    private function applyPaymentToInvoice(string $invoiceId, string $amount): void
    {
        $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);
        $newAmountPaid = bcadd($invoice->amount_paid, $amount, 8);
        $newAmountDue = bcsub($invoice->total, $newAmountPaid, 8);

        $status = bccomp($newAmountDue, '0', 8) <= 0
            ? InvoiceStatus::Paid
            : (bccomp($newAmountPaid, '0', 8) > 0 ? InvoiceStatus::Partial : $invoice->status);

        $invoice->update([
            'amount_paid' => $newAmountPaid,
            'amount_due' => bccomp($newAmountDue, '0', 8) >= 0 ? $newAmountDue : '0',
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Paid ? now() : null,
        ]);
    }
}
