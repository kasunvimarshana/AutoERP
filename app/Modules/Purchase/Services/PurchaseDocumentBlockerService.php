<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\PurchaseReturn;

final class PurchaseDocumentBlockerService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseProcurementBalanceService $balances,
        private readonly PurchaseDocumentLockService $locks,
    ) {}

    /**
     * @return array{code: string, reason: string}|null
     */
    public function purchaseOrderCloseBlocker(PurchaseOrder $order, bool $lockRelated = false): ?array
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            if ($this->positive($this->balances->remainingReceivableForPurchaseOrderLine($line))) {
                return ['code' => 'remaining_receivable', 'reason' => 'Purchase order has remaining receivable quantities.'];
            }
            if ($this->positive($this->balances->remainingInvoiceableForPurchaseOrderLine($line))) {
                return ['code' => 'remaining_invoiceable', 'reason' => 'Purchase order has remaining invoiceable quantities.'];
            }
        }

        if (! $lockRelated && $this->hasCapabilityProjection($order, [
            'draft_goods_receipts_count',
            'unresolved_purchase_invoices_count',
            'unresolved_purchase_returns_count',
            'unresolved_purchase_debit_notes_count',
        ])) {
            return $this->purchaseOrderProjectedCloseBlocker($order);
        }

        $goodsReceiptIds = GoodsReceiptNote::query()
            ->where('purchase_order_id', $order->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $goodsReceipts = $lockRelated
            ? $this->locks->goodsReceipts($goodsReceiptIds)
            : GoodsReceiptNote::query()->whereIn('id', $goodsReceiptIds)->orderBy('id')->get();

        if ($lockRelated && $goodsReceiptIds !== []) {
            $goodsReceiptLineIds = GoodsReceiptNoteLine::query()
                ->whereIn('goods_receipt_note_id', $goodsReceiptIds)
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $this->locks->goodsReceiptLines($goodsReceiptLineIds);
        }

        $draftGoodsReceipts = $goodsReceipts
            ->filter(fn (GoodsReceiptNote $grn): bool => $this->statusValue($grn->status) === GoodsReceiptNoteStatus::Draft->value)
            ->map(fn (GoodsReceiptNote $grn): string => (string) ($grn->grn_number ?: '#'.$grn->getKey()))
            ->values()
            ->all();
        if ($draftGoodsReceipts !== []) {
            return ['code' => 'draft_goods_receipts', 'reason' => 'Purchase order cannot be closed while draft GRNs exist: '.implode(', ', $draftGoodsReceipts).'.'];
        }

        if ($this->hasUnresolvedInvoices((int) $order->getKey(), $goodsReceiptIds, $lockRelated)) {
            return ['code' => 'unresolved_invoices', 'reason' => 'Purchase order cannot be closed while supplier invoices are unresolved.'];
        }

        $returns = $this->returnsForGoodsReceipts($goodsReceiptIds, $lockRelated);
        $unresolvedReturns = $returns
            ->filter(fn (PurchaseReturn $return): bool => in_array($this->statusValue($return->status), [PurchaseReturnStatus::Draft->value, PurchaseReturnStatus::Approved->value], true))
            ->map(fn (PurchaseReturn $return): string => (string) ($return->return_number ?: '#'.$return->getKey()))
            ->values()
            ->all();
        if ($unresolvedReturns !== []) {
            return ['code' => 'unresolved_returns', 'reason' => 'Purchase order cannot be closed while purchase returns are unresolved: '.implode(', ', $unresolvedReturns).'.'];
        }

        if ($this->hasUnresolvedDebitNotes($returns, $lockRelated)) {
            return ['code' => 'unresolved_debit_notes', 'reason' => 'Purchase order cannot be closed while purchase debit notes are unresolved.'];
        }

        return null;
    }

    /**
     * @return array{code: string, reason: string}|null
     */
    public function goodsReceiptReverseBlocker(GoodsReceiptNote $grn, bool $lockReturns = false): ?array
    {
        $grn->loadMissing('lines');
        $invoiced = '0.000000';
        $returned = '0.000000';
        foreach ($grn->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine) {
                continue;
            }
            $invoiced = $this->math->add($invoiced, (string) $line->invoiced_quantity);
            $returned = $this->math->add($returned, (string) $line->returned_quantity);
        }

        if ($this->positive($invoiced)) {
            return ['code' => 'has_invoiced_quantity', 'reason' => 'GRNs with invoiced lines cannot be reversed.'];
        }
        if ($this->positive($returned)) {
            return ['code' => 'has_returned_quantity', 'reason' => 'GRNs with returned lines cannot be reversed.'];
        }

        if (! $lockReturns && $this->hasCapabilityProjection($grn, ['unresolved_purchase_returns_count'])) {
            return ((int) $grn->getAttribute('unresolved_purchase_returns_count')) > 0
                ? ['code' => 'unresolved_returns', 'reason' => 'Cannot reverse GRN while purchase returns are unresolved or impacting.']
                : null;
        }

        $returns = $lockReturns
            ? $this->locks->purchaseReturnsForGoodsReceipt((int) $grn->getKey())
            : PurchaseReturn::query()
                ->where('source_type', 'goods_receipt_note')
                ->where('source_id', $grn->getKey())
                ->orderBy('id')
                ->get();
        if ($lockReturns) {
            $this->locks->purchaseReturnLinesForReturns($returns->pluck('id')->map(static fn ($id): int => (int) $id)->all());
        }

        $blocking = $returns
            ->filter(fn (PurchaseReturn $return): bool => $this->statusValue($return->status) !== PurchaseReturnStatus::Cancelled->value)
            ->map(fn (PurchaseReturn $return): string => (string) ($return->return_number ?: '#'.$return->getKey()))
            ->values()
            ->all();

        return $blocking === []
            ? null
            : ['code' => 'unresolved_returns', 'reason' => 'Cannot reverse GRN while purchase returns are unresolved or impacting: '.implode(', ', $blocking).'.'];
    }

    private function purchaseOrderProjectedCloseBlocker(PurchaseOrder $order): ?array
    {
        if (((int) $order->getAttribute('draft_goods_receipts_count')) > 0) {
            return ['code' => 'draft_goods_receipts', 'reason' => 'Purchase order has unresolved draft GRNs.'];
        }
        if (((int) $order->getAttribute('unresolved_purchase_invoices_count')) > 0) {
            return ['code' => 'unresolved_invoices', 'reason' => 'Purchase order has unresolved supplier invoices.'];
        }
        if (((int) $order->getAttribute('unresolved_purchase_returns_count')) > 0) {
            return ['code' => 'unresolved_returns', 'reason' => 'Purchase order has unresolved purchase returns.'];
        }
        if (((int) $order->getAttribute('unresolved_purchase_debit_notes_count')) > 0) {
            return ['code' => 'unresolved_debit_notes', 'reason' => 'Purchase order has unresolved purchase debit notes.'];
        }

        return null;
    }

    /**
     * @param  list<int>  $goodsReceiptIds
     */
    private function hasUnresolvedInvoices(int $purchaseOrderId, array $goodsReceiptIds, bool $lock): bool
    {
        $links = PurchaseInvoiceLink::query()
            ->where(function ($query) use ($purchaseOrderId, $goodsReceiptIds): void {
                $query->where(function ($scope) use ($purchaseOrderId): void {
                    $scope->where('source_type', 'purchase_order')
                        ->where('source_id', $purchaseOrderId);
                });
                if ($goodsReceiptIds !== []) {
                    $query->orWhere(function ($scope) use ($goodsReceiptIds): void {
                        $scope->where('source_type', 'goods_receipt_note')
                            ->whereIn('source_id', $goodsReceiptIds);
                    });
                }
            })
            ->orderBy('id');
        if ($lock) {
            $links->lockForUpdate();
        }

        $invoiceIds = $links->get()
            ->pluck('invoice_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        if ($invoiceIds === []) {
            return false;
        }

        $invoices = Invoice::query()->whereIn('id', $invoiceIds)->orderBy('id');
        if ($lock) {
            $invoices->lockForUpdate();
        }

        return $invoices
            ->get()
            ->contains(fn (Invoice $invoice): bool => in_array($this->statusValue($invoice->status), [InvoiceStatus::Draft->value, InvoiceStatus::Approved->value], true));
    }

    /**
     * @param  list<int>  $goodsReceiptIds
     * @return Collection<int, PurchaseReturn>
     */
    private function returnsForGoodsReceipts(array $goodsReceiptIds, bool $lock): Collection
    {
        if ($goodsReceiptIds === []) {
            return collect();
        }

        $returns = PurchaseReturn::query()
            ->where('source_type', 'goods_receipt_note')
            ->whereIn('source_id', $goodsReceiptIds)
            ->orderBy('id');
        if ($lock) {
            $returns->lockForUpdate();
        }

        $rows = $returns->get();
        if ($lock) {
            $this->locks->purchaseReturnLinesForReturns($rows->pluck('id')->map(static fn ($id): int => (int) $id)->all());
        }

        return $rows;
    }

    /**
     * @param  Collection<int, PurchaseReturn>  $returns
     */
    private function hasUnresolvedDebitNotes(Collection $returns, bool $lock): bool
    {
        $returnIds = $returns->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        if ($returnIds === []) {
            return false;
        }

        $notes = PurchaseDebitNote::query()
            ->whereIn('purchase_return_id', $returnIds)
            ->orderBy('id');
        if ($lock) {
            $notes->lockForUpdate();
        }

        return $notes
            ->get()
            ->contains(function (PurchaseDebitNote $note): bool {
                $status = $this->statusValue($note->status);
                if (in_array($status, [PurchaseDebitNoteStatus::Draft->value, PurchaseDebitNoteStatus::Approved->value], true)) {
                    return true;
                }

                return $status === PurchaseDebitNoteStatus::Posted->value
                    && $this->positive((string) $note->remaining_amount);
            });
    }

    /**
     * @param  list<string>  $attributes
     */
    private function hasCapabilityProjection(object $model, array $attributes): bool
    {
        if (! method_exists($model, 'getAttributes')) {
            return false;
        }

        $present = $model->getAttributes();
        foreach ($attributes as $attribute) {
            if (! array_key_exists($attribute, $present)) {
                return false;
            }
        }

        return true;
    }

    private function positive(string $amount): bool
    {
        return $this->math->compare($amount, '0.000000') > 0;
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
