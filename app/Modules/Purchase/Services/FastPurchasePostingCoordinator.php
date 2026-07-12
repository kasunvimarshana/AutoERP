<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Invoice\Constants\InvoiceFinanceSource;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Constants\PurchaseFinanceSource;
use Modules\Purchase\Models\GoodsReceiptNote;

final class FastPurchasePostingCoordinator
{
    public function __construct(private readonly FastPurchaseDocumentBuilder $documents) {}

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    public function createDocuments(array $resolved): array
    {
        $purchaseOrder = $this->documents->createPurchaseOrder($resolved);
        $goodsReceipt = null;
        $invoice = null;
        $payment = null;

        if ((bool) $resolved['options']['receive_stock_now']) {
            $goodsReceipt = $this->documents->createGoodsReceipt($resolved, $purchaseOrder);
        }

        if ((bool) $resolved['options']['create_supplier_invoice_now']) {
            $invoice = $this->documents->createSupplierInvoice($resolved, $purchaseOrder, $goodsReceipt);
        }

        if ((bool) $resolved['options']['record_payment_now']) {
            if (! $invoice instanceof Invoice) {
                throw new InvalidArgumentException('Supplier payment requires a supplier invoice.');
            }

            $payment = $this->documents->createSupplierPayment($resolved, $invoice);
        }

        return [
            'purchase_order' => $purchaseOrder,
            'goods_receipt' => $goodsReceipt,
            'supplier_invoice' => $invoice,
            'supplier_payment' => $payment,
            'finance_postings' => $this->ownerFinancePostings($goodsReceipt, $invoice),
        ];
    }

    /** @return list<PostingResultData> */
    private function ownerFinancePostings(?GoodsReceiptNote $goodsReceipt, ?Invoice $invoice): array
    {
        $postings = [];
        if ($goodsReceipt instanceof GoodsReceiptNote) {
            $journal = $this->journal(
                PurchaseFinanceSource::MODULE,
                PurchaseFinanceSource::GOODS_RECEIPT,
                (int) $goodsReceipt->getKey(),
            );
            if ($journal instanceof FinanceJournalEntry) {
                $postings[] = $this->result($journal);
            }
        }
        if ($invoice instanceof Invoice) {
            $journal = $this->journal(
                InvoiceFinanceSource::MODULE,
                InvoiceFinanceSource::POSTING_TYPE,
                (int) $invoice->getKey(),
            );
            if ($journal instanceof FinanceJournalEntry) {
                $postings[] = $this->result($journal);
            }
        }

        return $postings;
    }

    private function journal(string $module, string $type, int $sourceId): ?FinanceJournalEntry
    {
        return FinanceJournalEntry::query()
            ->withCount('ledgerEntries')
            ->where('source_module', $module)
            ->where('source_type', $type)
            ->where('source_id', $sourceId)
            ->where('status', JournalStatus::Posted->value)
            ->first();
    }

    private function result(FinanceJournalEntry $journal): PostingResultData
    {
        $status = $journal->status instanceof JournalStatus
            ? $journal->status->value
            : (string) $journal->status;

        return new PostingResultData(
            journalId: (int) $journal->getKey(),
            journalNumber: (string) $journal->journal_number,
            status: $status,
            totalDebit: (string) $journal->total_debit,
            totalCredit: (string) $journal->total_credit,
            ledgerEntryCount: (int) $journal->ledger_entries_count,
        );
    }
}
