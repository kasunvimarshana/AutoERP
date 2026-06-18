<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
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
use Modules\Purchase\Models\PurchaseReturnLine;

final class PurchaseDocumentCapabilityService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseProcurementBalanceService $balances,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forPurchaseOrder(PurchaseOrder $order): array
    {
        $order->loadMissing('lines');
        $status = $this->statusValue($order->status);
        $received = $this->sum($order->lines, 'received_quantity');
        $invoiced = $this->sum($order->lines, 'invoiced_quantity');
        $remainingReceivable = $this->sumRemaining($order->lines, fn (PurchaseOrderLine $line): string => $this->balances->remainingReceivableForPurchaseOrderLine($line));
        $remainingInvoiceable = $this->sumRemaining($order->lines, fn (PurchaseOrderLine $line): string => $this->balances->remainingInvoiceableForPurchaseOrderLine($line));

        $approved = $status === PurchaseOrderStatus::Approved->value;
        $hasReceivedOrInvoiced = $this->positive($received) || $this->positive($invoiced);

        $closeBlocker = $approved ? $this->purchaseOrderCloseBlocker($order, $remainingReceivable, $remainingInvoiceable) : null;

        return $this->resource([
            'can_edit' => $this->result($status === PurchaseOrderStatus::Draft->value, 'not_draft', 'Only draft purchase orders can be edited.'),
            'can_submit' => $this->result($status === PurchaseOrderStatus::Draft->value, 'not_draft', 'Only draft purchase orders can be submitted.'),
            'can_approve' => $this->result($status === PurchaseOrderStatus::PendingApproval->value, 'not_pending_approval', 'Only submitted purchase orders can be approved.'),
            'can_receive' => $this->result($approved && $this->positive($remainingReceivable), $approved ? 'fully_received' : 'not_approved', $approved ? 'Purchase order has no remaining receivable quantity.' : 'Only approved purchase orders can be received.'),
            'can_invoice' => $this->result($approved && $this->positive($remainingInvoiceable), $approved ? 'fully_invoiced' : 'not_approved', $approved ? 'Purchase order has no remaining invoiceable quantity.' : 'Only approved purchase orders can be invoiced.'),
            'can_close' => $this->result($approved && $closeBlocker === null, $closeBlocker['code'] ?? 'not_approved', $closeBlocker['reason'] ?? 'Only approved purchase orders can be closed.'),
            'can_force_close' => $this->result(false, 'unsupported_workflow', 'Force close is not supported.'),
            'can_cancel' => $this->result(in_array($status, [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::PendingApproval->value, PurchaseOrderStatus::Approved->value], true) && ! $hasReceivedOrInvoiced, $hasReceivedOrInvoiced ? 'has_activity' : 'invalid_lifecycle', $hasReceivedOrInvoiced ? 'Purchase orders with received or invoiced quantities cannot be cancelled.' : 'Purchase order cannot be cancelled in its current lifecycle state.'),
            'can_delete' => $this->result($status === PurchaseOrderStatus::Draft->value && ! $hasReceivedOrInvoiced, $hasReceivedOrInvoiced ? 'has_activity' : 'not_draft', $hasReceivedOrInvoiced ? 'Purchase orders with activity cannot be deleted.' : 'Only unused draft purchase orders can be deleted.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forGoodsReceipt(GoodsReceiptNote $grn): array
    {
        $grn->loadMissing(['lines.purchaseOrderLine.order']);
        $status = $this->statusValue($grn->status);
        $posted = $status === GoodsReceiptNoteStatus::Posted->value;
        $remainingInvoiceable = '0.000000';
        $remainingReturnable = '0.000000';
        $invoiced = '0.000000';
        $returned = '0.000000';
        $linkedOrderBlocked = false;

        foreach ($grn->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine) {
                continue;
            }
            $remainingInvoiceable = $this->math->add($remainingInvoiceable, $this->balances->remainingInvoiceableForGoodsReceiptLine($line));
            $remainingReturnable = $this->math->add($remainingReturnable, $this->balances->remainingReturnableForGoodsReceiptLine($line));
            $invoiced = $this->math->add($invoiced, (string) $line->invoiced_quantity);
            $returned = $this->math->add($returned, (string) $line->returned_quantity);
            $linkedOrderBlocked = $linkedOrderBlocked || $this->linkedPurchaseOrderClosed($line);
        }

        $reverseBlocker = $posted ? $this->goodsReceiptReverseBlocker($grn, $invoiced, $returned) : null;

        return $this->resource([
            'can_post' => $this->result($status === GoodsReceiptNoteStatus::Draft->value, 'not_draft', 'Only draft GRNs can be posted.'),
            'can_invoice' => $this->result($posted && ! $linkedOrderBlocked && $this->positive($remainingInvoiceable), ! $posted ? 'not_posted' : ($linkedOrderBlocked ? 'linked_order_closed' : 'fully_invoiced'), ! $posted ? 'Only posted GRNs can be invoiced.' : ($linkedOrderBlocked ? 'Linked purchase order is closed or cancelled.' : 'GRN has no remaining invoiceable quantity.')),
            'can_return' => $this->result($posted && ! $linkedOrderBlocked && $this->positive($remainingReturnable), ! $posted ? 'not_posted' : ($linkedOrderBlocked ? 'linked_order_closed' : 'fully_returned'), ! $posted ? 'Only posted GRNs can be returned.' : ($linkedOrderBlocked ? 'Linked purchase order is closed or cancelled.' : 'GRN has no remaining returnable quantity.')),
            'can_reverse' => $this->result($posted && $reverseBlocker === null, $reverseBlocker['code'] ?? 'not_posted', $reverseBlocker['reason'] ?? 'Only posted GRNs can be reversed.'),
            'read_only' => $this->result(in_array($status, [GoodsReceiptNoteStatus::Posted->value, GoodsReceiptNoteStatus::Reversed->value], true), 'editable', 'Draft GRNs are editable.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forPurchaseReturn(PurchaseReturn $return): array
    {
        $status = $this->statusValue($return->status);
        $approvalRequired = (bool) $return->approval_required;

        return $this->resource([
            'can_approve' => $this->result($approvalRequired && $status === PurchaseReturnStatus::Draft->value, $approvalRequired ? 'not_draft' : 'approval_not_required', $approvalRequired ? 'Only draft purchase returns can be approved.' : 'This purchase return does not require approval.'),
            'can_post' => $this->result(($approvalRequired && $status === PurchaseReturnStatus::Approved->value) || (! $approvalRequired && $status === PurchaseReturnStatus::Draft->value), $approvalRequired ? 'not_approved' : 'not_draft', $approvalRequired ? 'Purchase return must be approved before posting.' : 'Only draft purchase returns can be posted without approval.'),
            'can_cancel' => $this->result(in_array($status, [PurchaseReturnStatus::Draft->value, PurchaseReturnStatus::Approved->value], true), 'posted_or_cancelled', 'Only draft or approved purchase returns can be cancelled.'),
            'read_only' => $this->result(in_array($status, [PurchaseReturnStatus::Posted->value, PurchaseReturnStatus::Cancelled->value], true), 'editable', 'Draft and approved purchase returns are editable.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forDebitNote(PurchaseDebitNote $note): array
    {
        $status = $this->statusValue($note->status);
        $hasRemaining = $this->positive((string) $note->remaining_amount);

        return $this->resource([
            'can_approve' => $this->result($status === PurchaseDebitNoteStatus::Draft->value, 'not_draft', 'Only draft purchase debit notes can be approved.'),
            'can_post' => $this->result($status === PurchaseDebitNoteStatus::Approved->value, 'not_approved', 'Only approved purchase debit notes can be posted.'),
            'can_allocate' => $this->result($status === PurchaseDebitNoteStatus::Posted->value && $hasRemaining, $status === PurchaseDebitNoteStatus::Posted->value ? 'fully_allocated' : 'not_posted', $status === PurchaseDebitNoteStatus::Posted->value ? 'Purchase debit note has no remaining amount to allocate.' : 'Only posted purchase debit notes can be allocated.'),
            'read_only' => $this->result($status === PurchaseDebitNoteStatus::Posted->value, 'editable', 'Draft and approved purchase debit notes are editable.'),
        ]);
    }

    /**
     * @param  array<string, array{allowed: bool, code: string|null, reason: string|null}>  $capabilities
     * @return array<string, mixed>
     */
    private function resource(array $capabilities): array
    {
        $data = ['details' => $capabilities];

        foreach ($capabilities as $key => $capability) {
            $data[$key] = $capability['allowed'];
        }

        return $data;
    }

    /**
     * @return array{allowed: bool, code: string|null, reason: string|null}
     */
    private function result(bool $allowed, ?string $blockedCode, ?string $blockedReason): array
    {
        return [
            'allowed' => $allowed,
            'code' => $allowed ? null : $blockedCode,
            'reason' => $allowed ? null : $blockedReason,
        ];
    }

    private function positive(string $amount): bool
    {
        return $this->math->compare($amount, '0.000000') > 0;
    }

    /**
     * @param  Collection<int, object>  $lines
     */
    private function sum(Collection $lines, string $column): string
    {
        $total = '0.000000';
        foreach ($lines as $line) {
            $total = $this->math->add($total, (string) ($line->{$column} ?? '0.000000'));
        }

        return $total;
    }

    /**
     * @param  Collection<int, PurchaseOrderLine>  $lines
     */
    private function sumRemaining(Collection $lines, callable $resolver): string
    {
        $total = '0.000000';
        foreach ($lines as $line) {
            if ($line instanceof PurchaseOrderLine) {
                $total = $this->math->add($total, $resolver($line));
            }
        }

        return $total;
    }

    /**
     * @return array{code: string, reason: string}|null
     */
    private function purchaseOrderCloseBlocker(PurchaseOrder $order, string $remainingReceivable, string $remainingInvoiceable): ?array
    {
        if ($this->positive($remainingReceivable)) {
            return ['code' => 'remaining_receivable', 'reason' => 'Purchase order has remaining receivable quantities.'];
        }
        if ($this->positive($remainingInvoiceable)) {
            return ['code' => 'remaining_invoiceable', 'reason' => 'Purchase order has remaining invoiceable quantities.'];
        }

        $goodsReceiptIds = GoodsReceiptNote::query()
            ->where('purchase_order_id', $order->getKey())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if (GoodsReceiptNote::query()->whereIn('id', $goodsReceiptIds)->where('status', GoodsReceiptNoteStatus::Draft->value)->exists()) {
            return ['code' => 'draft_goods_receipts', 'reason' => 'Purchase order has unresolved draft GRNs.'];
        }

        if ($this->hasUnresolvedInvoices((int) $order->getKey(), $goodsReceiptIds)) {
            return ['code' => 'unresolved_invoices', 'reason' => 'Purchase order has unresolved supplier invoices.'];
        }

        $returnIds = PurchaseReturn::query()
            ->where('source_type', 'goods_receipt_note')
            ->whereIn('source_id', $goodsReceiptIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if (PurchaseReturn::query()->whereIn('id', $returnIds)->whereIn('status', [PurchaseReturnStatus::Draft->value, PurchaseReturnStatus::Approved->value])->exists()) {
            return ['code' => 'unresolved_returns', 'reason' => 'Purchase order has unresolved purchase returns.'];
        }

        if (PurchaseDebitNote::query()
            ->whereIn('purchase_return_id', $returnIds)
            ->where(function ($query): void {
                $query->whereIn('status', [PurchaseDebitNoteStatus::Draft->value, PurchaseDebitNoteStatus::Approved->value])
                    ->orWhere(function ($posted): void {
                        $posted->where('status', PurchaseDebitNoteStatus::Posted->value)
                            ->whereRaw('remaining_amount > 0');
                    });
            })
            ->exists()) {
            return ['code' => 'unresolved_debit_notes', 'reason' => 'Purchase order has unresolved purchase debit notes.'];
        }

        return null;
    }

    /**
     * @param  list<int>  $goodsReceiptIds
     */
    private function hasUnresolvedInvoices(int $purchaseOrderId, array $goodsReceiptIds): bool
    {
        $invoiceIds = PurchaseInvoiceLink::query()
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
            ->pluck('invoice_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $invoiceIds !== []
            && Invoice::query()
                ->whereIn('id', $invoiceIds)
                ->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Approved->value])
                ->exists();
    }

    /**
     * @return array{code: string, reason: string}|null
     */
    private function goodsReceiptReverseBlocker(GoodsReceiptNote $grn, string $invoiced, string $returned): ?array
    {
        if ($this->positive($invoiced)) {
            return ['code' => 'has_invoiced_quantity', 'reason' => 'GRNs with invoiced lines cannot be reversed.'];
        }
        if ($this->positive($returned)) {
            return ['code' => 'has_returned_quantity', 'reason' => 'GRNs with returned lines cannot be reversed.'];
        }

        if (PurchaseReturn::query()
            ->where('source_type', 'goods_receipt_note')
            ->where('source_id', $grn->getKey())
            ->where('status', '!=', PurchaseReturnStatus::Cancelled->value)
            ->exists()) {
            return ['code' => 'unresolved_returns', 'reason' => 'Cannot reverse GRN while purchase returns are unresolved or impacting.'];
        }

        return null;
    }

    private function linkedPurchaseOrderClosed(GoodsReceiptNoteLine $line): bool
    {
        if (! $line->purchaseOrderLine instanceof PurchaseOrderLine) {
            return false;
        }

        $status = $this->statusValue($line->purchaseOrderLine->order?->status);

        return in_array($status, [PurchaseOrderStatus::Closed->value, PurchaseOrderStatus::Cancelled->value], true);
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
