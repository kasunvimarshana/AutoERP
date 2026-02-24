<?php

namespace Modules\Inventory\Domain\Enums;

enum StockValuationMethod: string
{
    case FIFO             = 'fifo';
    case WeightedAverage  = 'weighted_average';
}
