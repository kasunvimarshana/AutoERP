<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Delivered = 'delivered';
    case Invoiced = 'invoiced';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
