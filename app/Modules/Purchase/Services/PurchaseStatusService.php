<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;

final class PurchaseStatusService
{
    public function assertPurchaseOrderTransition(PurchaseOrderStatus $from, PurchaseOrderStatus $to): void
    {
        $allowed = [
            PurchaseOrderStatus::Draft->value => [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::Approved->value => [PurchaseOrderStatus::PartiallyReceived, PurchaseOrderStatus::Received, PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled],
            PurchaseOrderStatus::PartiallyReceived->value => [PurchaseOrderStatus::Received, PurchaseOrderStatus::PartiallyInvoiced, PurchaseOrderStatus::Closed],
            PurchaseOrderStatus::Received->value => [PurchaseOrderStatus::PartiallyInvoiced, PurchaseOrderStatus::Invoiced, PurchaseOrderStatus::Closed],
            PurchaseOrderStatus::PartiallyInvoiced->value => [PurchaseOrderStatus::Invoiced, PurchaseOrderStatus::Closed],
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

    private function assertAllowed(string $from, string $to, array $allowed, string $message): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw new InvalidArgumentException($message);
        }
    }
}
