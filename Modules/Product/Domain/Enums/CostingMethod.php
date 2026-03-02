<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

enum CostingMethod: string
{
    case Fifo = 'fifo';
    case Lifo = 'lifo';
    case WeightedAverage = 'weighted_average';

    public function label(): string
    {
        return match ($this) {
            CostingMethod::Fifo => 'FIFO (First In, First Out)',
            CostingMethod::Lifo => 'LIFO (Last In, First Out)',
            CostingMethod::WeightedAverage => 'Weighted Average',
        };
    }
}
