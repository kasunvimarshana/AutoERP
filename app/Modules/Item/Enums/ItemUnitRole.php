<?php

declare(strict_types=1);

namespace Modules\Item\Enums;

enum ItemUnitRole: string
{
    case Base = 'base';
    case Purchase = 'purchase';
    case Sales = 'sales';
    case Service = 'service';
    case Rental = 'rental';
}
