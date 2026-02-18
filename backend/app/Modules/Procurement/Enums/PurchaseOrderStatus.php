<?php

namespace App\Modules\Procurement\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ORDERED = 'ordered';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::ORDERED => 'Ordered',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    public function canApprove(): bool
    {
        return $this === self::PENDING;
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::APPROVED, self::ORDERED, self::PARTIALLY_RECEIVED]);
    }
}
