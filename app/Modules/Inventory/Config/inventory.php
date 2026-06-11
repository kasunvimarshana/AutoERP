<?php

declare(strict_types=1);

use Modules\Inventory\Strategies\Allocation\BatchAllocationStrategy;
use Modules\Inventory\Strategies\Allocation\FefoAllocationStrategy;
use Modules\Inventory\Strategies\Allocation\FifoAllocationStrategy;
use Modules\Inventory\Strategies\Allocation\ManualAllocationStrategy;
use Modules\Inventory\Strategies\Allocation\SerialAllocationStrategy;
use Modules\Inventory\Strategies\Valuation\FIFOValuationMethod;
use Modules\Inventory\Strategies\Valuation\ManualCostValuationMethod;
use Modules\Inventory\Strategies\Valuation\StandardCostValuationMethod;
use Modules\Inventory\Strategies\Valuation\WeightedAverageValuationMethod;

return [
    'allow_negative_stock' => (bool) env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),

    'valuation' => [
        'default' => 'fifo',
        'strategies' => [
            'fifo' => FIFOValuationMethod::class,
            'weighted_average' => WeightedAverageValuationMethod::class,
            'standard' => StandardCostValuationMethod::class,
            'standard_cost' => StandardCostValuationMethod::class,
            'manual' => ManualCostValuationMethod::class,
            'manual_cost' => ManualCostValuationMethod::class,
        ],
    ],
    'allocation' => [
        'default' => 'fifo',
        'strategies' => [
            'fifo' => FifoAllocationStrategy::class,
            'fefo' => FefoAllocationStrategy::class,
            'batch' => BatchAllocationStrategy::class,
            'serial' => SerialAllocationStrategy::class,
            'manual' => ManualAllocationStrategy::class,
        ],
    ],
];
