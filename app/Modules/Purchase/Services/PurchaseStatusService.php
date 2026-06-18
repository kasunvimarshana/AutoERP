<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
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
    public function assertPurchaseOrderTransition(PurchaseOrderStatus $from, PurchaseOrderStatus $to): void
    {
        $allowed = [
            PurchaseOrderStatus::Draft->value => [PurchaseOrderStatus::PendingApproval, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::PendingApproval->value => [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::Approved->value => [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
        ];

        $this->assertAllowed($from->value, $to->value, $allowed, 'Invalid purchase order status transition.');
    }

    public function assertGoodsReceiptTransition(GoodsReceiptNoteStatus $from, GoodsReceiptNoteStatus $to): void
    {
        $allowed = [
            GoodsReceiptNoteStatus::Draft->value => [GoodsReceiptNoteStatus::Posted],
            GoodsReceiptNoteStatus::Posted->value => [GoodsReceiptNoteStatus::Reversed],
        ];

        $this->assertAllowed($from->value, $to->value, $allowed, 'Invalid goods receipt note status transition.');
    }

    public function assertPurchaseReturnTransition(PurchaseReturnStatus $from, PurchaseReturnStatus $to): void
    {
        $allowed = [
            PurchaseReturnStatus::Draft->value => [PurchaseReturnStatus::Approved, PurchaseReturnStatus::Posted, PurchaseReturnStatus::Cancelled],
            PurchaseReturnStatus::Approved->value => [PurchaseReturnStatus::Posted, PurchaseReturnStatus::Cancelled],
        ];

        $this->assertAllowed($from->value, $to->value, $allowed, 'Invalid purchase return status transition.');
    }

    public function refreshGoodsReceipt(GoodsReceiptNote $goodsReceipt): GoodsReceiptNote
    {
        $goodsReceipt->loadMissing('lines');
        $status = $goodsReceipt->status instanceof GoodsReceiptNoteStatus
            ? $goodsReceipt->status
            : GoodsReceiptNoteStatus::from((string) $goodsReceipt->status);

        if (in_array($status, [GoodsReceiptNoteStatus::Draft, GoodsReceiptNoteStatus::Reversed], true)) {
            return $goodsReceipt;
        }

        foreach ($goodsReceipt->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine) {
                continue;
            }

            $lineStatus = GoodsReceiptNoteLineStatus::Posted;
            if ($line->status !== $lineStatus) {
                $line->status = $lineStatus;
                $line->save();
            }
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

        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }

            $lineStatus = PurchaseOrderLineStatus::Open;
            if ($line->status !== $lineStatus) {
                $line->status = $lineStatus;
                $line->save();
            }
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

}
