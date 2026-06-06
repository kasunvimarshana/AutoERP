<?php

declare(strict_types=1);

namespace Modules\Item\Enums;

enum CostingMethod: string
{
    case Fifo = 'fifo';
    case WeightedAverage = 'weighted_average';
    case Standard = 'standard';
    case Manual = 'manual';
    case None = 'none';
}
