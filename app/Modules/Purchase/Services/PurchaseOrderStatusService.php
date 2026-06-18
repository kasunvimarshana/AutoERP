<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\PurchaseReturn;

final class PurchaseOrderStatusService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseStatusService $transitions,
        private readonly PurchaseDocumentLockService $locks,
        private readonly PurchaseProcurementBalanceService $balances,
    ) {}

    public function submit(PurchaseOrder $order, ?int $submittedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $submittedBy): PurchaseOrder {
            $locked = $this->lock($order);
            $this->transitions->assertPurchaseOrderTransition(
                $locked->status,
                PurchaseOrderStatus::PendingApproval,
            );
            $locked->status = PurchaseOrderStatus::PendingApproval;
            $locked->submitted_by = $submittedBy;
            $locked->submitted_at = now();
            $locked->save();

            return $locked;
        });
    }

    public function approve(PurchaseOrder $order, ?int $approvedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approvedBy): PurchaseOrder {
            $locked = $this->lock($order);
            $this->transitions->assertPurchaseOrderTransition($locked->status, PurchaseOrderStatus::Approved);
            $locked->status = PurchaseOrderStatus::Approved;
            $locked->approved_by = $approvedBy;
            $locked->approved_at = now();
            $locked->save();

            return $locked;
        });
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order): PurchaseOrder {
            $locked = $this->lock($order, ['lines']);
            $this->assertCancellable($locked);
            $this->transitions->assertPurchaseOrderTransition($locked->status, PurchaseOrderStatus::Cancelled);
            $locked->status = PurchaseOrderStatus::Cancelled;
            $locked->save();

            return $locked;
        });
    }

    public function close(PurchaseOrder $order, ?int $closedBy = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $closedBy): PurchaseOrder {
            $locked = $this->lock($order, ['lines']);
            $this->transitions->assertPurchaseOrderTransition($locked->status, PurchaseOrderStatus::Closed);
            $this->assertClosable($locked);
            $locked->status = PurchaseOrderStatus::Closed;
            $locked->closed_by = $closedBy;
            $locked->closed_at = now();
            $locked->save();

            return $locked;
        });
    }

    private function assertCancellable(PurchaseOrder $order): void
    {
        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if ($this->math->compare((string) $line->received_quantity, '0.000000') > 0
                || $this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
                throw new InvalidArgumentException(
                    'Purchase orders with received or invoiced quantities cannot be cancelled.',
                );
            }
        }
    }

    private function assertClosable(PurchaseOrder $order): void
    {
        foreach ($order->lines as $line) {
            if ($this->math->compare($this->balances->remainingReceivableForPurchaseOrderLine($line), '0.000000') > 0) {
                throw new InvalidArgumentException(
                    'Purchase orders with remaining receivable quantities cannot be closed.',
                );
            }
            if ($this->math->compare($this->balances->remainingInvoiceableForPurchaseOrderLine($line), '0.000000') > 0) {
                throw new InvalidArgumentException(
                    'Purchase orders with remaining invoiceable quantities cannot be closed.',
                );
            }
        }

        $goodsReceiptIds = GoodsReceiptNote::query()
            ->where('purchase_order_id', $order->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $goodsReceipts = $this->locks->goodsReceipts($goodsReceiptIds);
        if ($goodsReceiptIds !== []) {
            $goodsReceiptLineIds = GoodsReceiptNoteLine::query()
                ->whereIn('goods_receipt_note_id', $goodsReceiptIds)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $this->locks->goodsReceiptLines($goodsReceiptLineIds);
        }

        $draftGoodsReceipts = $goodsReceipts
            ->filter(fn (GoodsReceiptNote $grn): bool => $grn->status === GoodsReceiptNoteStatus::Draft)
            ->map(fn (GoodsReceiptNote $grn): string => (string) ($grn->grn_number ?: '#'.$grn->getKey()))
            ->values()
            ->all();
        if ($draftGoodsReceipts !== []) {
            throw new InvalidArgumentException('Purchase order cannot be closed while draft GRNs exist: '.implode(', ', $draftGoodsReceipts).'.');
        }

        $this->assertNoUnresolvedInvoices($order, $goodsReceiptIds);
        $returns = $this->lockReturnsForGoodsReceipts($goodsReceiptIds);
        $this->assertNoUnresolvedReturns($returns);
        $this->assertNoUnresolvedDebitNotes($returns);
    }

    /**
     * @param  list<string>  $relations
     */
    private function lock(PurchaseOrder $order, array $relations = []): PurchaseOrder
    {
        $locked = $this->locks->purchaseOrders([(int) $order->getKey()])->first();
        if (! $locked instanceof PurchaseOrder) {
            throw new InvalidArgumentException('Purchase order was not found.');
        }

        if (in_array('lines', $relations, true)) {
            $lines = PurchaseOrderLine::query()
                ->where('purchase_order_id', $locked->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $locked->setRelation('lines', $lines);
        }

        return $locked;
    }

    /**
     * @param  list<int>  $goodsReceiptIds
     */
    private function assertNoUnresolvedInvoices(PurchaseOrder $order, array $goodsReceiptIds): void
    {
        $links = PurchaseInvoiceLink::query()
            ->where(function ($query) use ($order, $goodsReceiptIds): void {
                $query->where(function ($scope) use ($order): void {
                    $scope->where('source_type', 'purchase_order')
                        ->where('source_id', $order->getKey());
                });
                if ($goodsReceiptIds !== []) {
                    $query->orWhere(function ($scope) use ($goodsReceiptIds): void {
                        $scope->where('source_type', 'goods_receipt_note')
                            ->whereIn('source_id', $goodsReceiptIds);
                    });
                }
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $invoiceIds = $links->pluck('invoice_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        if ($invoiceIds === []) {
            return;
        }

        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $blocking = $invoices
            ->filter(fn (Invoice $invoice): bool => in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Approved], true))
            ->map(fn (Invoice $invoice): string => (string) ($invoice->invoice_number ?: '#'.$invoice->getKey()))
            ->values()
            ->all();
        if ($blocking !== []) {
            throw new InvalidArgumentException('Purchase order cannot be closed while supplier invoices are unresolved: '.implode(', ', $blocking).'.');
        }
    }

    /**
     * @param  list<int>  $goodsReceiptIds
     * @return \Illuminate\Database\Eloquent\Collection<int, PurchaseReturn>
     */
    private function lockReturnsForGoodsReceipts(array $goodsReceiptIds)
    {
        if ($goodsReceiptIds === []) {
            return PurchaseReturn::query()->whereRaw('1 = 0')->get();
        }

        $returns = PurchaseReturn::query()
            ->where('source_type', 'goods_receipt_note')
            ->whereIn('source_id', $goodsReceiptIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $returnIds = $returns->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $this->locks->purchaseReturnLinesForReturns($returnIds);

        return $returns;
    }

    /**
     * @param  iterable<PurchaseReturn>  $returns
     */
    private function assertNoUnresolvedReturns(iterable $returns): void
    {
        $blocking = [];
        foreach ($returns as $return) {
            if (! in_array($return->status, [PurchaseReturnStatus::Draft, PurchaseReturnStatus::Approved], true)) {
                continue;
            }
            $blocking[] = (string) ($return->return_number ?: '#'.$return->getKey());
        }

        if ($blocking !== []) {
            throw new InvalidArgumentException('Purchase order cannot be closed while purchase returns are unresolved: '.implode(', ', $blocking).'.');
        }
    }

    /**
     * @param  iterable<PurchaseReturn>  $returns
     */
    private function assertNoUnresolvedDebitNotes(iterable $returns): void
    {
        $returnIds = [];
        foreach ($returns as $return) {
            $returnIds[] = (int) $return->getKey();
        }
        if ($returnIds === []) {
            return;
        }

        $notes = PurchaseDebitNote::query()
            ->whereIn('purchase_return_id', $returnIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $blocking = $notes
            ->filter(function (PurchaseDebitNote $note): bool {
                if (in_array($note->status, [PurchaseDebitNoteStatus::Draft, PurchaseDebitNoteStatus::Approved], true)) {
                    return true;
                }

                return $note->status === PurchaseDebitNoteStatus::Posted
                    && $this->math->compare((string) $note->remaining_amount, '0.000000') > 0;
            })
            ->map(fn (PurchaseDebitNote $note): string => (string) ($note->debit_note_number ?: '#'.$note->getKey()))
            ->values()
            ->all();

        if ($blocking !== []) {
            throw new InvalidArgumentException('Purchase order cannot be closed while purchase debit notes are unresolved: '.implode(', ', $blocking).'.');
        }
    }
}
