<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum ValuationMethod: string
{
    case FIFO = 'fifo';
    case WeightedAverage = 'weighted_average';
    case Standard = 'standard';
    case Manual = 'manual';
}
