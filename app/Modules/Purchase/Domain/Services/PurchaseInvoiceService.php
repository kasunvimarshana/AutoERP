<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Purchase\Domain\Enums\DocumentReferenceType;
use Modules\Purchase\Domain\Enums\InvoiceStatus;
use Modules\Purchase\Domain\Enums\PurchaseInvoiceType;
use Modules\Purchase\Domain\Events\PurchaseInvoicePosted;
use Modules\Purchase\Domain\Repositories\PurchaseAggregateRepositoryInterface;
use Modules\Sequence\Domain\Services\SequenceService;

final class PurchaseInvoiceService
{
    public function __construct(
        private readonly PurchaseAggregateRepositoryInterface $repository,
        private readonly SequenceService $sequences,
        private readonly PurchaseTotalsService $totals,
        private readonly JournalEntryService $journals,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $payload['direction'] = 'inbound';
            $payload['invoice_type'] ??= PurchaseInvoiceType::Standard->value;
            $payload['invoice_number'] ??= $this->sequences->next(
                'purchase_invoice',
                (int) $payload['tenant_id'],
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            );
            $payload['status'] = InvoiceStatus::Draft->value;
            $payload['party_type'] = 'supplier';
            $payload['paid_amount'] = 0;
            $payload['ap_account_id'] ??= DB::table('suppliers')
                ->where('id', (int) $payload['party_id'])
                ->value('ap_account_id');

            if (isset($payload['source_type'], $payload['source_id']) && empty($payload['references'])) {
                $payload['references'] = [[
                    'document_type' => strtoupper((string) $payload['source_type']) === 'GRN'
                        ? DocumentReferenceType::GoodsReceipt->value
                        : DocumentReferenceType::PurchaseOrder->value,
                    'document_id' => (int) $payload['source_id'],
                    'reference' => $payload['reference'] ?? null,
                ]];
            }

            $payload['lines'] = $this->calculateLines($payload['lines'] ?? [], $payload);
            $payload = array_merge($payload, $this->totals->calculateHeader($payload['lines'], $payload));
            $payload['balance'] = $payload['grand_total'];
            unset($payload['source_type'], $payload['source_id']);

            return $this->repository->createPurchaseInvoice($payload);
        });
    }

    public function approve(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $invoice = $this->repository->findInvoiceForUpdate($id);
            if ($invoice === null) {
                throw new \RuntimeException('Purchase invoice not found.');
            }

            if (($invoice['status'] ?? null) === InvoiceStatus::Approved->value) {
                return $invoice;
            }

            foreach (($invoice['lines'] ?? []) as $line) {
                $this->updateSourceInvoicedQuantity($line);
            }

            $journalEntryId = $this->journals->createPurchaseInvoice($invoice);
            $posted = $this->repository->updateInvoice($id, [
                'status' => InvoiceStatus::Approved->value,
                'paid_amount' => 0,
                'balance' => $invoice['grand_total'],
                'journal_entry_id' => $journalEntryId,
            ]);

            $this->refreshReferencedPurchaseOrders($invoice);
            event(new PurchaseInvoicePosted($id));

            return $posted;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $header
     * @return array<int, array<string, mixed>>
     */
    private function calculateLines(array $lines, array $header): array
    {
        return array_map(function (array $line) use ($header): array {
            $line['tenant_id'] ??= $header['tenant_id'];
            $line['organization_unit_id'] ??= $header['organization_unit_id'] ?? null;
            $line['item_type'] ??= 'item';

            return $this->totals->calculateLine($line, 'quantity');
        }, $lines);
    }

    /** @param array<string, mixed> $line */
    private function updateSourceInvoicedQuantity(array $line): void
    {
        $reference = $line['reference'] ?? null;
        if (! is_string($reference) || ! str_contains($reference, ':')) {
            return;
        }

        [$type, $id] = explode(':', $reference, 2);
        $table = match ($type) {
            'GRN_LINE' => 'grn_lines',
            'PO_LINE' => 'purchase_order_lines',
            default => null,
        };

        if ($table === null) {
            return;
        }

        $source = DB::table($table)->lockForUpdate()->find((int) $id);
        if ($source === null) {
            return;
        }

        $maxQty = $table === 'grn_lines'
            ? (float) $source->received_qty
            : ((float) $source->received_qty > 0 ? (float) $source->received_qty : (float) $source->ordered_qty);
        $newInvoicedQty = (float) $source->invoiced_qty + (float) $line['quantity'];
        if ($newInvoicedQty > $maxQty) {
            throw new \RuntimeException('Invoice quantity exceeds source document quantity.');
        }

        DB::table($table)->where('id', (int) $source->id)->update([
            'invoiced_qty' => $newInvoicedQty,
            'row_version' => (int) $source->row_version + 1,
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $invoice */
    private function refreshReferencedPurchaseOrders(array $invoice): void
    {
        foreach (($invoice['references'] ?? []) as $reference) {
            if (($reference['document_type'] ?? null) === DocumentReferenceType::PurchaseOrder->value) {
                $this->refreshPurchaseOrderInvoiceStatus((int) $reference['document_id']);
            }

            if (($reference['document_type'] ?? null) === DocumentReferenceType::GoodsReceipt->value) {
                $grn = DB::table('grn_headers')->find((int) $reference['document_id']);
                $this->refreshGrnInvoiceStatus((int) $reference['document_id']);
                if ($grn !== null && $grn->purchase_order_id !== null) {
                    $this->refreshPurchaseOrderInvoiceStatus((int) $grn->purchase_order_id);
                }
            }
        }
    }

    private function refreshPurchaseOrderInvoiceStatus(int $purchaseOrderId): void
    {
        $lines = DB::table('purchase_order_lines')->where('purchase_order_id', $purchaseOrderId)->get();
        $ordered = (float) $lines->sum('ordered_qty');
        $invoiced = (float) $lines->sum('invoiced_qty');
        $status = $invoiced <= 0 ? 'not_invoiced' : ($invoiced >= $ordered ? 'closed' : 'partially_invoiced');

        DB::table('purchase_orders')->where('id', $purchaseOrderId)->update([
            'invoice_status' => $status,
            'updated_at' => now(),
        ]);
    }

    private function refreshGrnInvoiceStatus(int $grnId): void
    {
        $lines = DB::table('grn_lines')->where('grn_header_id', $grnId)->get();
        $received = (float) $lines->sum('received_qty');
        $invoiced = (float) $lines->sum('invoiced_qty');
        $status = $invoiced <= 0 ? 'not_invoiced' : ($invoiced >= $received ? 'closed' : 'partially_invoiced');

        DB::table('grn_headers')->where('id', $grnId)->update([
            'invoice_status' => $status,
            'updated_at' => now(),
        ]);
    }
}
