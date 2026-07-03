<?php

declare(strict_types=1);

namespace Modules\Purchase\Constants;

final class PurchaseAuditEvent
{
    public const FAST_PURCHASE_COMPLETED = 'purchase.fast_purchase.completed';
    public const PURCHASE_ORDER_CREATED = 'purchase.order.created';
    public const PURCHASE_ORDER_UPDATED = 'purchase.order.updated';
    public const PURCHASE_ORDER_DELETED = 'purchase.order.deleted';
    public const PURCHASE_ORDER_SUBMITTED = 'purchase.order.submitted';
    public const PURCHASE_ORDER_APPROVED = 'purchase.order.approved';
    public const PURCHASE_ORDER_CANCELLED = 'purchase.order.cancelled';
    public const PURCHASE_ORDER_CLOSED = 'purchase.order.closed';
    public const GOODS_RECEIPT_CREATED = 'purchase.goods_receipt.created';
    public const GOODS_RECEIPT_POSTED = 'purchase.goods_receipt.posted';
    public const GOODS_RECEIPT_REVERSED = 'purchase.goods_receipt.reversed';
    public const PURCHASE_RETURN_CREATED = 'purchase.return.created';
    public const PURCHASE_RETURN_APPROVED = 'purchase.return.approved';
    public const PURCHASE_RETURN_POSTED = 'purchase.return.posted';
    public const PURCHASE_RETURN_CANCELLED = 'purchase.return.cancelled';
    public const PURCHASE_DEBIT_NOTE_CREATED = 'purchase.debit_note.created';
    public const PURCHASE_DEBIT_NOTE_APPROVED = 'purchase.debit_note.approved';
    public const PURCHASE_DEBIT_NOTE_POSTED = 'purchase.debit_note.posted';
    public const PURCHASE_DEBIT_NOTE_ALLOCATED = 'purchase.debit_note.allocated';

    private function __construct() {}
}
