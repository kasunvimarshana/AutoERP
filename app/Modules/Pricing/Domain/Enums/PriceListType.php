<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Enums;

enum PriceListType: string
{
    case Purchase = 'purchase';
    case Sales = 'sales';
}
