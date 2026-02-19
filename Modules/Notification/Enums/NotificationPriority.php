<?php

declare(strict_types=1);

namespace Modules\Notification\Enums;

enum NotificationPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low Priority',
            self::NORMAL => 'Normal Priority',
            self::HIGH => 'High Priority',
            self::URGENT => 'Urgent',
        };
    }
}
