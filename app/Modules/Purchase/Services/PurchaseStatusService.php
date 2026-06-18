<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseStatusService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function assertPurchaseOrderTransition(PurchaseOrderStatus $from, PurchaseOrderStatus $to): void
    {
        $allowed = [
            PurchaseOrderStatus::Draft->value => [PurchaseOrderStatus::PendingApproval, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::PendingApproval->value => [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::Approved->value => [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::PartiallyReceived->value => [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::Received->value => [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::PartiallyInvoiced->value => [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::Invoiced->value => [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::PartiallyReturned->value => [PurchaseOrderStatus::Closed],
            PurchaseOrderStatus::Returned->value => [PurchaseOrderStatus::Closed],
        ];

        $this->assertAllowed($from->value, $to->value, $allowed, 'Invalid purchase order status transition.');
    }

    public function assertGoodsReceiptTransition(GoodsReceiptNoteStatus $from, GoodsReceiptNoteStatus $to): void
    {
        $allowed = [
            GoodsReceiptNoteStatus::Draft->value => [GoodsReceiptNoteStatus::Posted, GoodsReceiptNoteStatus::Cancelled],
            GoodsReceiptNoteStatus::Posted->value => [GoodsReceiptNoteStatus::PartiallyReturned, GoodsReceiptNoteStatus::Returned, GoodsReceiptNoteStatus::PartiallyInvoiced, GoodsReceiptNoteStatus::Invoiced, GoodsReceiptNoteStatus::Reversed],
            GoodsReceiptNoteStatus::PartiallyInvoiced->value => [GoodsReceiptNoteStatus::Invoiced],
            GoodsReceiptNoteStatus::PartiallyReturned->value => [GoodsReceiptNoteStatus::Returned],
        ];

        $this->assertAllowed($from->value, $to->value, $allowed, 'Invalid goods receipt note status transition.');
    }

    public function assertPurchaseReturnTransition(PurchaseReturnStatus $from, PurchaseReturnStatus $to): void
    {
        $allowed = [
            PurchaseReturnStatus::Draft->value => [PurchaseReturnStatus::Approved, PurchaseReturnStatus::Posted, PurchaseReturnStatus::Cancelled],
            PurchaseReturnStatus::Approved->value => [PurchaseReturnStatus::Posted, PurchaseReturnStatus::Cancelled],
            PurchaseReturnStatus::Posted->value => [PurchaseReturnStatus::Reversed],
        ];

        $this->assertAllowed($from->value, $to->value, $allowed, 'Invalid purchase return status transition.');
    }

    public function refreshGoodsReceipt(GoodsReceiptNote $goodsReceipt): GoodsReceiptNote
    {
        $goodsReceipt->loadMissing('lines');
        $status = $goodsReceipt->status instanceof GoodsReceiptNoteStatus
            ? $goodsReceipt->status
            : GoodsReceiptNoteStatus::from((string) $goodsReceipt->status);

        if (in_array($status, [
            GoodsReceiptNoteStatus::Draft,
            GoodsReceiptNoteStatus::Cancelled,
            GoodsReceiptNoteStatus::Reversed,
        ], true)) {
            return $goodsReceipt;
        }

        $lineStatuses = [];
        foreach ($goodsReceipt->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine) {
                continue;
            }

            $lineStatus = $this->goodsReceiptLineStatus($line);
            if ($line->status !== $lineStatus) {
                $line->status = $lineStatus;
                $line->save();
            }
            $lineStatuses[] = $lineStatus;
        }

        if ($lineStatuses === []) {
            return $goodsReceipt;
        }

        $nextStatus = $this->goodsReceiptHeaderStatus($lineStatuses);
        if ($goodsReceipt->status !== $nextStatus) {
            $goodsReceipt->status = $nextStatus;
            $goodsReceipt->save();
        }

        return $goodsReceipt->refresh()->load('lines');
    }

    public function refreshPurchaseOrder(PurchaseOrder $order): PurchaseOrder
    {
        $order->loadMissing('lines');
        $status = $order->status instanceof PurchaseOrderStatus
            ? $order->status
            : PurchaseOrderStatus::from((string) $order->status);

        if (in_array($status, [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::PendingApproval,
            PurchaseOrderStatus::Closed,
            PurchaseOrderStatus::Cancelled,
        ], true)) {
            return $order;
        }

        $lineStatuses = [];
        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }

            $lineStatus = $this->purchaseOrderLineStatus($line);
            if ($line->status !== $lineStatus) {
                $line->status = $lineStatus;
                $line->save();
            }
            $lineStatuses[] = $lineStatus;
        }

        if ($lineStatuses === []) {
            return $order;
        }

        $nextStatus = $this->purchaseOrderHeaderStatus($order);
        if ($order->status !== $nextStatus) {
            $order->status = $nextStatus;
            $order->save();
        }

        return $order->refresh()->load('lines');
    }

    private function assertAllowed(string $from, string $to, array $allowed, string $message): void
    {
        if ($from === $to) {
            return;
        }

        foreach ($allowed[$from] ?? [] as $candidate) {
            $candidateValue = $candidate instanceof \BackedEnum ? $candidate->value : (string) $candidate;
            if ($candidateValue === $to) {
                return;
            }
        }

        throw new InvalidArgumentException($message);
    }

    private function goodsReceiptLineStatus(GoodsReceiptNoteLine $line): GoodsReceiptNoteLineStatus
    {
        if ($this->math->compare((string) $line->returned_quantity, '0.000000') > 0) {
            return $this->math->compare((string) $line->returned_quantity, (string) $line->accepted_quantity) >= 0
                ? GoodsReceiptNoteLineStatus::Returned
                : GoodsReceiptNoteLineStatus::PartiallyReturned;
        }

        if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            return $this->math->compare((string) $line->invoiced_quantity, (string) $line->accepted_quantity) >= 0
                ? GoodsReceiptNoteLineStatus::Invoiced
                : GoodsReceiptNoteLineStatus::PartiallyInvoiced;
        }

        return GoodsReceiptNoteLineStatus::Posted;
    }

    /**
     * @param  list<GoodsReceiptNoteLineStatus>  $lineStatuses
     */
    private function goodsReceiptHeaderStatus(array $lineStatuses): GoodsReceiptNoteStatus
    {
        if ($this->all($lineStatuses, GoodsReceiptNoteLineStatus::Returned)) {
            return GoodsReceiptNoteStatus::Returned;
        }
        if ($this->containsAny($lineStatuses, [
            GoodsReceiptNoteLineStatus::Returned,
            GoodsReceiptNoteLineStatus::PartiallyReturned,
        ])) {
            return GoodsReceiptNoteStatus::PartiallyReturned;
        }
        if ($this->all($lineStatuses, GoodsReceiptNoteLineStatus::Invoiced)) {
            return GoodsReceiptNoteStatus::Invoiced;
        }
        if ($this->containsAny($lineStatuses, [
            GoodsReceiptNoteLineStatus::Invoiced,
            GoodsReceiptNoteLineStatus::PartiallyInvoiced,
        ])) {
            return GoodsReceiptNoteStatus::PartiallyInvoiced;
        }

        return GoodsReceiptNoteStatus::Posted;
    }

    private function purchaseOrderLineStatus(PurchaseOrderLine $line): PurchaseOrderLineStatus
    {
        $ordered = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);

        if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            return $this->math->compare((string) $line->invoiced_quantity, $ordered) >= 0
                ? PurchaseOrderLineStatus::Invoiced
                : PurchaseOrderLineStatus::PartiallyInvoiced;
        }

        if ($this->math->compare((string) $line->received_quantity, '0.000000') > 0) {
            return $this->math->compare((string) $line->received_quantity, $ordered) >= 0
                ? PurchaseOrderLineStatus::Received
                : PurchaseOrderLineStatus::PartiallyReceived;
        }

        return PurchaseOrderLineStatus::Open;
    }

    private function purchaseOrderHeaderStatus(PurchaseOrder $order): PurchaseOrderStatus
    {
        $ordered = '0.000000';
        $received = '0.000000';
        $invoiced = '0.000000';
        $returned = '0.000000';

        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }

            $ordered = $this->math->add($ordered, $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity));
            $received = $this->math->add($received, (string) $line->received_quantity);
            $invoiced = $this->math->add($invoiced, (string) $line->invoiced_quantity);
            $returned = $this->math->add($returned, (string) $line->returned_quantity);
        }

        if ($this->math->compare($returned, '0.000000') > 0) {
            return $this->math->compare($returned, $received) >= 0
                ? PurchaseOrderStatus::Returned
                : PurchaseOrderStatus::PartiallyReturned;
        }

        if ($this->math->compare($invoiced, '0.000000') > 0) {
            return $this->math->compare($invoiced, $ordered) >= 0
                ? PurchaseOrderStatus::Invoiced
                : PurchaseOrderStatus::PartiallyInvoiced;
        }

        if ($this->math->compare($received, '0.000000') > 0) {
            return $this->math->compare($received, $ordered) >= 0
                ? PurchaseOrderStatus::Received
                : PurchaseOrderStatus::PartiallyReceived;
        }

        return PurchaseOrderStatus::Approved;
    }

    /**
     * @template T of \BackedEnum
     * @param  list<T>  $values
     * @param  T  $expected
     */
    private function all(array $values, \BackedEnum $expected): bool
    {
        foreach ($values as $value) {
            if ($value !== $expected) {
                return false;
            }
        }

        return $values !== [];
    }

    /**
     * @param  list<\BackedEnum>  $values
     * @param  list<\BackedEnum>  $expected
     */
    private function containsAny(array $values, array $expected): bool
    {
        foreach ($values as $value) {
            foreach ($expected as $candidate) {
                if ($value === $candidate) {
                    return true;
                }
            }
        }

        return false;
    }
}
