<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\Enums;
enum PaymentStatus: string {
    case PENDING  = 'pending';
    case PARTIAL  = 'partial';
    case PAID     = 'paid';
    case OVERDUE  = 'overdue';
    case REFUNDED = 'refunded';
    case VOID     = 'void';
    public function label(): string {
        return match($this) {
            self::PENDING  => 'Pending',
            self::PARTIAL  => 'Partial',
            self::PAID     => 'Paid',
            self::OVERDUE  => 'Overdue',
            self::REFUNDED => 'Refunded',
            self::VOID     => 'Void',
        };
    }
}
