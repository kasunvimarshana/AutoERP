<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\Enums\SalesCreditNoteStatus;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesReturn;

final class SalesDocumentCapabilityService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesFulfilmentBalanceService $balances,
        private readonly SalesDocumentBlockerService $blockers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forSalesOrder(SalesOrder $order): array
    {
        $order->loadMissing('lines');
        $status = $this->statusValue($order->status);
        $approved = $status === SalesOrderStatus::Approved->value;
        $allocated = $this->sum($order->lines, 'allocated_quantity');
        $delivered = $this->sum($order->lines, 'delivered_quantity');
        $invoiced = $this->sum($order->lines, 'invoiced_quantity');
        $returned = $this->sum($order->lines, 'returned_quantity');
        $remainingAllocatable = $this->sumRemaining($order->lines, fn (SalesOrderLine $line): string => $this->balances->remainingAllocatableForSalesOrderLine($line));
        $remainingDeliverable = $this->sumRemaining($order->lines, fn (SalesOrderLine $line): string => $this->balances->remainingDeliverableForSalesOrderLine($line));
        $remainingInvoiceable = $this->sumRemaining($order->lines, fn (SalesOrderLine $line): string => $this->balances->remainingInvoiceableForSalesOrderLine($line));
        $remainingReturnable = $this->sumRemaining($order->lines, fn (SalesOrderLine $line): string => $this->balances->remainingReturnableForSalesOrderLine($line));
        $hasActivity = $this->positive($allocated) || $this->positive($delivered) || $this->positive($invoiced) || $this->positive($returned);
        $closeBlocker = $approved ? $this->blockers->salesOrderCloseBlocker($order) : null;

        return $this->resource([
            'can_edit' => $this->result($status === SalesOrderStatus::Draft->value, 'not_draft', 'Only draft sales orders can be edited.'),
            'can_submit' => $this->result($status === SalesOrderStatus::Draft->value, 'not_draft', 'Only draft sales orders can be submitted.'),
            'can_approve' => $this->result($status === SalesOrderStatus::PendingApproval->value, 'not_pending_approval', 'Only submitted sales orders can be approved.'),
            'can_allocate' => $this->result($approved && $this->positive($remainingAllocatable), $approved ? 'fully_allocated' : 'not_approved', $approved ? 'Sales order has no remaining allocatable quantity.' : 'Only approved sales orders can be allocated.'),
            'can_release_allocation' => $this->result($approved && $this->positive($allocated) && ! $this->positive($delivered), ! $approved ? 'not_approved' : ($this->positive($delivered) ? 'has_deliveries' : 'no_allocations'), ! $approved ? 'Only approved sales orders can release allocations.' : ($this->positive($delivered) ? 'Delivered allocations cannot be released from the order.' : 'Sales order has no allocations to release.')),
            'can_deliver' => $this->result($approved && $this->positive($remainingDeliverable), $approved ? 'fully_delivered' : 'not_approved', $approved ? 'Sales order has no remaining deliverable quantity.' : 'Only approved sales orders can be delivered.'),
            'can_invoice' => $this->result($approved && $this->positive($remainingInvoiceable), $approved ? 'fully_invoiced' : 'not_approved', $approved ? 'Sales order has no remaining invoiceable quantity.' : 'Only approved sales orders can be invoiced.'),
            'can_receive_payment' => $this->result($this->positive($invoiced), 'not_invoiced', 'Sales order must have customer invoices before receipts can be prepared.'),
            'can_return' => $this->result($approved && $this->positive($remainingReturnable), $approved ? 'fully_returned' : 'not_approved', $approved ? 'Sales order has no remaining returnable quantity.' : 'Only approved sales orders can be returned.'),
            'can_cancel' => $this->result(in_array($status, [SalesOrderStatus::Draft->value, SalesOrderStatus::PendingApproval->value, SalesOrderStatus::Approved->value], true) && ! $hasActivity, $hasActivity ? 'has_activity' : 'invalid_lifecycle', $hasActivity ? 'Sales orders with allocation, delivery, invoice, or return activity cannot be cancelled.' : 'Sales order cannot be cancelled in its current lifecycle state.'),
            'can_close' => $this->result($approved && $closeBlocker === null, $closeBlocker['code'] ?? 'not_approved', $closeBlocker['reason'] ?? 'Only approved sales orders can be closed.'),
            'can_delete' => $this->result($status === SalesOrderStatus::Draft->value && ! $hasActivity, $hasActivity ? 'has_activity' : 'not_draft', $hasActivity ? 'Sales orders with activity cannot be deleted.' : 'Only unused draft sales orders can be deleted.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSalesDelivery(SalesDelivery $delivery): array
    {
        $delivery->loadMissing('lines.salesOrderLine.order');
        $status = $this->statusValue($delivery->status);
        $posted = $status === SalesDeliveryStatus::Posted->value;
        $remainingInvoiceable = '0.000000';
        $remainingReturnable = '0.000000';

        foreach ($delivery->lines as $line) {
            if (! $line instanceof SalesDeliveryLine) {
                continue;
            }
            $remainingInvoiceable = $this->math->add($remainingInvoiceable, $this->balances->remainingInvoiceableForSalesDeliveryLine($line));
            $remainingReturnable = $this->math->add($remainingReturnable, $this->balances->remainingReturnableForSalesDeliveryLine($line));
        }

        $reverseBlocker = $posted ? $this->blockers->salesDeliveryReverseBlocker($delivery) : null;

        return $this->resource([
            'can_post' => $this->result($status === SalesDeliveryStatus::Draft->value, 'not_draft', 'Only draft sales deliveries can be posted.'),
            'can_invoice' => $this->result($posted && $this->positive($remainingInvoiceable), $posted ? 'fully_invoiced' : 'not_posted', $posted ? 'Sales delivery has no remaining invoiceable quantity.' : 'Only posted sales deliveries can be invoiced.'),
            'can_return' => $this->result($posted && $this->positive($remainingReturnable), $posted ? 'fully_returned' : 'not_posted', $posted ? 'Sales delivery has no remaining returnable quantity.' : 'Only posted sales deliveries can be returned.'),
            'can_reverse' => $this->result($posted && $reverseBlocker === null, $reverseBlocker['code'] ?? 'not_posted', $reverseBlocker['reason'] ?? 'Only posted sales deliveries can be reversed.'),
            'read_only' => $this->result(in_array($status, [SalesDeliveryStatus::Posted->value, SalesDeliveryStatus::Reversed->value], true), 'editable', 'Draft sales deliveries are editable.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSalesReturn(SalesReturn $return): array
    {
        $status = $this->statusValue($return->status);
        $approvalRequired = (bool) $return->approval_required;

        return $this->resource([
            'can_edit' => $this->result($status === SalesReturnStatus::Draft->value, 'not_draft', 'Only draft sales returns can be edited.'),
            'can_submit' => $this->result($approvalRequired && $status === SalesReturnStatus::Draft->value, $approvalRequired ? 'not_draft' : 'approval_not_required', $approvalRequired ? 'Only draft sales returns can be submitted.' : 'This sales return does not require submission.'),
            'can_approve' => $this->result($approvalRequired && $status === SalesReturnStatus::Draft->value, $approvalRequired ? 'not_draft' : 'approval_not_required', $approvalRequired ? 'Only draft sales returns can be approved.' : 'This sales return does not require approval.'),
            'can_post' => $this->result(($approvalRequired && $status === SalesReturnStatus::Approved->value) || (! $approvalRequired && $status === SalesReturnStatus::Draft->value), $approvalRequired ? 'not_approved' : 'not_draft', $approvalRequired ? 'Sales return must be approved before posting.' : 'Only draft sales returns can be posted without approval.'),
            'can_reverse' => $this->result($status === SalesReturnStatus::Posted->value, 'not_posted', 'Only posted sales returns can be reversed.'),
            'can_cancel' => $this->result(in_array($status, [SalesReturnStatus::Draft->value, SalesReturnStatus::Approved->value], true), 'posted_or_cancelled', 'Only draft or approved sales returns can be cancelled.'),
            'read_only' => $this->result(in_array($status, [SalesReturnStatus::Posted->value, SalesReturnStatus::Cancelled->value, SalesReturnStatus::Reversed->value], true), 'editable', 'Draft and approved sales returns are editable.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forCreditNote(SalesCreditNote $note): array
    {
        $status = $this->statusValue($note->status);
        $hasRemaining = $this->positive((string) $note->remaining_amount);
        $reverseBlocker = in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value], true)
            ? $this->blockers->salesCreditNoteReverseBlocker($note)
            : null;

        return $this->resource([
            'can_edit' => $this->result($status === SalesCreditNoteStatus::Draft->value, 'not_draft', 'Only draft sales credit notes can be edited.'),
            'can_approve' => $this->result($status === SalesCreditNoteStatus::Draft->value, 'not_draft', 'Only draft sales credit notes can be approved.'),
            'can_post' => $this->result($status === SalesCreditNoteStatus::Approved->value, 'not_approved', 'Only approved sales credit notes can be posted.'),
            'can_allocate' => $this->result(in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value], true) && $hasRemaining, in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value], true) ? 'fully_allocated' : 'not_posted', in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value], true) ? 'Sales credit note has no remaining amount to allocate.' : 'Only posted sales credit notes can be allocated.'),
            'can_reverse' => $this->result($reverseBlocker === null && in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value], true), $reverseBlocker['code'] ?? 'not_posted', $reverseBlocker['reason'] ?? 'Only posted or allocated sales credit notes can be reversed.'),
            'read_only' => $this->result(in_array($status, [SalesCreditNoteStatus::Posted->value, SalesCreditNoteStatus::Allocated->value, SalesCreditNoteStatus::Reversed->value], true), 'editable', 'Draft and approved sales credit notes are editable.'),
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
     * @param  Collection<int, SalesOrderLine>  $lines
     */
    private function sumRemaining(Collection $lines, callable $resolver): string
    {
        $total = '0.000000';
        foreach ($lines as $line) {
            if ($line instanceof SalesOrderLine) {
                $total = $this->math->add($total, $resolver($line));
            }
        }

        return $total;
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
