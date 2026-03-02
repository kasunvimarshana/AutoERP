<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\Enums;
enum SaleStatus: string {
    case DRAFT      = 'draft';
    case CONFIRMED  = 'confirmed';
    case PROCESSING = 'processing';
    case COMPLETED  = 'completed';
    case CANCELLED  = 'cancelled';
    case REFUNDED   = 'refunded';
    public function label(): string {
        return match($this) {
            self::DRAFT      => 'Draft',
            self::CONFIRMED  => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::COMPLETED  => 'Completed',
            self::CANCELLED  => 'Cancelled',
            self::REFUNDED   => 'Refunded',
        };
    }
    public function canTransitionTo(self $new): bool {
        return match($this) {
            self::DRAFT      => in_array($new, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED  => in_array($new, [self::PROCESSING, self::CANCELLED]),
            self::PROCESSING => in_array($new, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED  => $new === self::REFUNDED,
            default          => false,
        };
    }
}
