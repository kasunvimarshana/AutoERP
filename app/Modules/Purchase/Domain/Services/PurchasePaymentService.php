<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Purchase\Domain\Enums\DocumentReferenceType;
use Modules\Purchase\Domain\Enums\PaymentDirection;
use Modules\Purchase\Domain\Events\PurchasePaymentProcessed;
use Modules\Purchase\Domain\Repositories\PurchaseAggregateRepositoryInterface;
use Modules\Sequence\Domain\Services\SequenceService;

final class PurchasePaymentService
{
    public function __construct(
        private readonly PurchaseAggregateRepositoryInterface $repository,
        private readonly SequenceService $sequences,
        private readonly JournalEntryService $journals,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            if (! empty($payload['idempotency_key'])) {
                $existing = DB::table('payments')
                    ->where('tenant_id', (int) $payload['tenant_id'])
                    ->where('idempotency_key', (string) $payload['idempotency_key'])
                    ->first();
                if ($existing !== null) {
                    return (array) $existing;
                }
            }

            $allocationTotal = round(array_sum(array_map(
                static fn (array $allocation): float => (float) ($allocation['allocated_amount'] ?? 0),
                $payload['allocations'] ?? [],
            )), 4);
            if ($allocationTotal > round((float) $payload['amount'], 4)) {
                throw new \RuntimeException('Payment allocations cannot exceed payment amount.');
            }

            $payload['payment_number'] ??= $this->sequences->next(
                'payment',
                (int) $payload['tenant_id'],
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            );
            $payload['direction'] = PaymentDirection::Outbound->value;
            $payload['party_type'] = 'supplier';
            $payload['base_amount'] = round((float) $payload['amount'] * (float) ($payload['exchange_rate'] ?? 1), 4);
            $payload['status'] = 'posted';

            $payment = $this->repository->createPayment($payload);

            foreach (($payment['allocations'] ?? []) as $allocation) {
                if (($allocation['document_type'] ?? null) !== DocumentReferenceType::Invoice->value) {
                    continue;
                }

                $this->allocateToInvoice((int) $allocation['document_id'], (float) $allocation['allocated_amount']);
                $this->refreshPurchaseOrdersFromInvoice((int) $allocation['document_id']);
            }

            $journalEntryId = $this->journals->createPaymentEntry($payment);
            DB::table('payments')->where('id', (int) $payment['id'])->update([
                'journal_entry_id' => $journalEntryId,
                'updated_at' => now(),
            ]);

            event(new PurchasePaymentProcessed((int) $payment['id']));

            return $payment;
        });
    }

    private function allocateToInvoice(int $invoiceId, float $amount): void
    {
        $invoice = DB::table('invoices')->lockForUpdate()->find($invoiceId);
        if ($invoice === null) {
            throw new \RuntimeException('Invoice not found for payment allocation.');
        }

        if ($invoice->direction !== 'inbound') {
            throw new \RuntimeException('Purchase payments can only settle inbound supplier invoices.');
        }

        if (! in_array($invoice->status, ['approved', 'partially_paid'], true)) {
            throw new \RuntimeException('Only approved purchase invoices can be paid.');
        }

        if ($amount > (float) $invoice->balance) {
            throw new \RuntimeException('Payment allocation exceeds invoice balance.');
        }

        $paid = round((float) $invoice->paid_amount + $amount, 4);
        $balance = round((float) $invoice->grand_total - $paid, 4);

        DB::table('invoices')->where('id', $invoiceId)->update([
            'paid_amount' => $paid,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'paid' : 'partially_paid',
            'row_version' => (int) $invoice->row_version + 1,
            'updated_at' => now(),
        ]);
    }

    private function refreshPurchaseOrdersFromInvoice(int $invoiceId): void
    {
        $references = DB::table('invoice_references')->where('invoice_id', $invoiceId)->get();
        foreach ($references as $reference) {
            $purchaseOrderId = null;
            if ($reference->document_type === DocumentReferenceType::PurchaseOrder->value) {
                $purchaseOrderId = (int) $reference->document_id;
            }

            if ($reference->document_type === DocumentReferenceType::GoodsReceipt->value) {
                $grn = DB::table('grn_headers')->find((int) $reference->document_id);
                $purchaseOrderId = $grn?->purchase_order_id === null ? null : (int) $grn->purchase_order_id;
            }

            if ($purchaseOrderId === null) {
                continue;
            }

            $invoiceIds = DB::table('invoice_references')
                ->where(function ($query) use ($purchaseOrderId): void {
                    $query->where(function ($inner) use ($purchaseOrderId): void {
                        $inner->where('document_type', DocumentReferenceType::PurchaseOrder->value)
                            ->where('document_id', $purchaseOrderId);
                    })->orWhere(function ($inner) use ($purchaseOrderId): void {
                        $inner->where('document_type', DocumentReferenceType::GoodsReceipt->value)
                            ->whereIn('document_id', function ($subQuery) use ($purchaseOrderId): void {
                                $subQuery->select('id')
                                    ->from('grn_headers')
                                    ->where('purchase_order_id', $purchaseOrderId);
                            });
                    });
                })
                ->pluck('invoice_id');

            $paid = (float) DB::table('invoices')->whereIn('id', $invoiceIds)->sum('paid_amount');
            $order = DB::table('purchase_orders')->lockForUpdate()->find($purchaseOrderId);
            if ($order === null) {
                continue;
            }

            DB::table('purchase_orders')->where('id', $purchaseOrderId)->update([
                'paid_amount' => $paid,
                'balance' => round((float) $order->grand_total - $paid, 4),
                'row_version' => (int) $order->row_version + 1,
                'updated_at' => now(),
            ]);
        }
    }
}
