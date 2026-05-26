<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Inventory\Domain\Services\InventoryTransactionService;
use Modules\Sales\Domain\Events\GdnConfirmed;
use Modules\Sales\Domain\Events\SalesInvoicePosted;
use Modules\Sales\Domain\Events\SalesReturnProcessed;
use Modules\Sequence\Domain\Services\SequenceService;

final class SalesLifecycleService
{
    public function __construct(
        private readonly InventoryTransactionService $inventory,
        private readonly JournalEntryService $journals,
        private readonly SequenceService $sequences,
    ) {
    }

    public function confirmSalesOrder(int $salesOrderId): array
    {
        return DB::transaction(function () use ($salesOrderId): array {
            $order = DB::table('sales_orders')->lockForUpdate()->find($salesOrderId);
            if ($order === null) {
                throw new \RuntimeException('Sales order not found.');
            }

            if ($order->status !== 'draft') {
                throw new \RuntimeException('Only draft sales orders can be confirmed.');
            }

            foreach (DB::table('sales_order_lines')->where('sales_order_id', $salesOrderId)->get() as $line) {
                $this->inventory->reserve(
                    (int) $line->item_id,
                    (float) $line->ordered_qty,
                    (int) ($line->warehouse_id ?? $order->warehouse_id),
                    'SALES_ORDER',
                    $salesOrderId,
                    (array) $line,
                );
            }

            DB::table('sales_orders')->where('id', $salesOrderId)->update([
                'status' => 'confirmed',
                'row_version' => (int) $order->row_version + 1,
                'updated_at' => now(),
            ]);

            return (array) DB::table('sales_orders')->find($salesOrderId);
        });
    }

    public function confirmGdn(int $gdnHeaderId): array
    {
        return DB::transaction(function () use ($gdnHeaderId): array {
            $gdn = DB::table('gdn_headers')->lockForUpdate()->find($gdnHeaderId);
            if ($gdn === null) {
                throw new \RuntimeException('GDN not found.');
            }

            if ($gdn->status === 'confirmed') {
                return (array) $gdn;
            }

            foreach (DB::table('gdn_lines')->where('gdn_header_id', $gdnHeaderId)->get() as $line) {
                $qty = (float) $line->delivered_qty;
                if ($line->sales_order_line_id !== null) {
                    $soLine = DB::table('sales_order_lines')->lockForUpdate()->find((int) $line->sales_order_line_id);
                    if ($soLine === null) {
                        throw new \RuntimeException('Sales order line not found.');
                    }

                    $delivered = (float) $soLine->delivered_qty + $qty;
                    if ($delivered + (float) $soLine->rejected_qty > (float) $soLine->ordered_qty) {
                        throw new \RuntimeException('GDN quantity exceeds ordered quantity.');
                    }

                    DB::table('sales_order_lines')->where('id', (int) $soLine->id)->update([
                        'delivered_qty' => $delivered,
                        'row_version' => (int) $soLine->row_version + 1,
                        'updated_at' => now(),
                    ]);
                }

                $this->inventory->release((int) $line->item_id, $qty, (int) ($line->warehouse_id ?? $gdn->warehouse_id), 'GDN', $gdnHeaderId, (array) $line);
                $this->inventory->issue((int) $line->item_id, $qty, (float) ($line->unit_cost ?? 0), (int) ($line->warehouse_id ?? $gdn->warehouse_id), 'SALES_ISSUE', (int) $line->id, (array) $line);
            }

            DB::table('gdn_headers')->where('id', $gdnHeaderId)->update([
                'status' => 'confirmed',
                'row_version' => (int) $gdn->row_version + 1,
                'updated_at' => now(),
            ]);

            if ($gdn->sales_order_id !== null) {
                $this->refreshDeliveryStatus((int) $gdn->sales_order_id);
            }

            event(new GdnConfirmed($gdnHeaderId));

            return (array) DB::table('gdn_headers')->find($gdnHeaderId);
        });
    }

    /** @param array<string, mixed> $payload */
    public function createInvoice(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $payload['invoice_number'] ??= $this->sequences->next('sales_invoice', (int) $payload['tenant_id'], $payload['organization_unit_id'] ?? null);
            $payload['direction'] = 'outbound';
            $payload['invoice_type'] = 'sale';
            $payload['status'] = 'draft';
            $payload['party_type'] = 'customer';
            $payload['paid_amount'] = 0;
            $payload['balance'] = $payload['grand_total'] ?? 0;

            $lines = $payload['lines'] ?? [];
            $references = $payload['references'] ?? [];
            unset($payload['lines'], $payload['references']);

            $invoiceId = (int) DB::table('invoices')->insertGetId(array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach (is_array($references) ? $references : [] as $reference) {
                DB::table('invoice_references')->insert(array_merge($reference, [
                    'tenant_id' => $payload['tenant_id'],
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'invoice_id' => $invoiceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            foreach (is_array($lines) ? $lines : [] as $line) {
                DB::table('invoice_lines')->insert(array_merge($line, [
                    'tenant_id' => $payload['tenant_id'],
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'invoice_id' => $invoiceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            return (array) DB::table('invoices')->find($invoiceId);
        });
    }

    public function approveInvoice(int $invoiceId): array
    {
        return DB::transaction(function () use ($invoiceId): array {
            $invoice = (array) DB::table('invoices')->lockForUpdate()->find($invoiceId);
            if ($invoice === []) {
                throw new \RuntimeException('Sales invoice not found.');
            }

            $entryId = $this->journals->createSalesInvoice($invoice);
            DB::table('invoices')->where('id', $invoiceId)->update([
                'status' => 'approved',
                'journal_entry_id' => $entryId,
                'balance' => $invoice['grand_total'] - (float) $invoice['paid_amount'],
                'row_version' => (int) $invoice['row_version'] + 1,
                'updated_at' => now(),
            ]);

            event(new SalesInvoicePosted($invoiceId));

            return (array) DB::table('invoices')->find($invoiceId);
        });
    }

    /** @param array<string, mixed> $payload */
    public function createPayment(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $payload['payment_number'] ??= $this->sequences->next('sales_payment', (int) $payload['tenant_id'], $payload['organization_unit_id'] ?? null);
            $payload['direction'] = 'inbound';
            $payload['party_type'] = 'customer';
            $payload['status'] = 'posted';
            $payload['base_amount'] = round((float) $payload['amount'] * (float) ($payload['exchange_rate'] ?? 1), 4);

            $allocations = $payload['allocations'] ?? [];
            unset($payload['allocations']);

            $paymentId = (int) DB::table('payments')->insertGetId(array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach (is_array($allocations) ? $allocations : [] as $allocation) {
                DB::table('payment_allocations')->insert(array_merge($allocation, [
                    'tenant_id' => $payload['tenant_id'],
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'payment_id' => $paymentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                if (($allocation['document_type'] ?? null) === 'INVOICE') {
                    $invoice = DB::table('invoices')->lockForUpdate()->find((int) $allocation['document_id']);
                    if ($invoice !== null) {
                        $paid = (float) $invoice->paid_amount + (float) $allocation['allocated_amount'];
                        DB::table('invoices')->where('id', (int) $invoice->id)->update([
                            'paid_amount' => $paid,
                            'balance' => round((float) $invoice->grand_total - $paid, 4),
                            'status' => $paid >= (float) $invoice->grand_total ? 'paid' : 'partially_paid',
                            'row_version' => (int) $invoice->row_version + 1,
                            'updated_at' => now(),
                        ]);

                        $this->refreshSalesOrderPaidAmount((int) $invoice->id);
                    }
                }
            }

            $payment = (array) DB::table('payments')->find($paymentId);
            $entryId = $this->journals->createPaymentEntry($payment);
            DB::table('payments')->where('id', $paymentId)->update(['journal_entry_id' => $entryId, 'updated_at' => now()]);

            return (array) DB::table('payments')->find($paymentId);
        });
    }

    public function approveReturn(int $salesReturnId): array
    {
        return DB::transaction(function () use ($salesReturnId): array {
            $return = (array) DB::table('sales_returns')->lockForUpdate()->find($salesReturnId);
            if ($return === []) {
                throw new \RuntimeException('Sales return not found.');
            }

            foreach (DB::table('sales_return_lines')->where('sales_return_id', $salesReturnId)->get() as $line) {
                $this->inventory->receive((int) $line->item_id, (float) $line->return_qty, (float) ($line->unit_cost ?? 0), (int) $line->warehouse_id, 'SALES_RETURN', (int) $line->id, (array) $line);
            }

            $entryId = $this->journals->createSalesReturnEntry($return);
            DB::table('sales_returns')->where('id', $salesReturnId)->update([
                'status' => 'approved',
                'metadata' => json_encode(array_merge((array) ($return['metadata'] ?? []), ['journal_entry_id' => $entryId])),
                'row_version' => (int) $return['row_version'] + 1,
                'updated_at' => now(),
            ]);

            event(new SalesReturnProcessed($salesReturnId));

            return (array) DB::table('sales_returns')->find($salesReturnId);
        });
    }

    private function refreshDeliveryStatus(int $salesOrderId): void
    {
        $lines = DB::table('sales_order_lines')->where('sales_order_id', $salesOrderId)->get();
        $ordered = (float) $lines->sum('ordered_qty');
        $delivered = (float) $lines->sum('delivered_qty');
        $status = $delivered >= $ordered ? 'delivered' : ($delivered > 0 ? 'partially_delivered' : 'confirmed');

        DB::table('sales_orders')->where('id', $salesOrderId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    private function refreshSalesOrderPaidAmount(int $invoiceId): void
    {
        $references = DB::table('invoice_references')->where('invoice_id', $invoiceId)->get();
        foreach ($references as $reference) {
            $salesOrderId = null;
            if ($reference->document_type === 'SO') {
                $salesOrderId = (int) $reference->document_id;
            }

            if ($reference->document_type === 'GDN') {
                $gdn = DB::table('gdn_headers')->find((int) $reference->document_id);
                $salesOrderId = $gdn?->sales_order_id === null ? null : (int) $gdn->sales_order_id;
            }

            if ($salesOrderId === null) {
                continue;
            }

            $invoiceIds = DB::table('invoice_references')
                ->where(function ($query) use ($salesOrderId): void {
                    $query->where(function ($inner) use ($salesOrderId): void {
                        $inner->where('document_type', 'SO')->where('document_id', $salesOrderId);
                    })->orWhere(function ($inner) use ($salesOrderId): void {
                        $inner->where('document_type', 'GDN')->whereIn('document_id', function ($subQuery) use ($salesOrderId): void {
                            $subQuery->select('id')->from('gdn_headers')->where('sales_order_id', $salesOrderId);
                        });
                    });
                })
                ->pluck('invoice_id');

            $paid = (float) DB::table('invoices')->whereIn('id', $invoiceIds)->sum('paid_amount');
            $order = DB::table('sales_orders')->lockForUpdate()->find($salesOrderId);
            if ($order === null) {
                continue;
            }

            DB::table('sales_orders')->where('id', $salesOrderId)->update([
                'paid_amount' => $paid,
                'balance' => round((float) $order->grand_total - $paid, 4),
                'row_version' => (int) $order->row_version + 1,
                'updated_at' => now(),
            ]);
        }
    }
}
