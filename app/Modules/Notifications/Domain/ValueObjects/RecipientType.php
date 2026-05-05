<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\ValueObjects;

enum RecipientType: string
{
    case System   = 'system';
    case User     = 'user';
    case Customer = 'customer';
    case Driver   = 'driver';
}
