<?php

declare(strict_types=1);

use Modules\Inventory\Domain\Strategies\Allocation\BatchAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\FefoAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\FifoAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\LifoAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\LocationPriorityAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\LotAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\ReservationAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\RuleBasedAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Allocation\SerialAllocationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\FifoValuationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\LifoValuationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\MovingAverageValuationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\ReplacementCostValuationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\SpecificIdentificationValuationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\StandardCostValuationStrategy;
use Modules\Inventory\Domain\Strategies\Valuation\WeightedAverageValuationStrategy;

return [
    'defaults' => [
        'valuation_enabled' => true,
        'allocation_enabled' => true,
    ],

    'feature_keys' => [
        'valuation' => [
            'inventory.valuation.enabled',
        ],
        'allocation' => [
            'inventory.allocation.enabled',
        ],
    ],

    'retries' => [
        'attempts' => 3,
    ],

    'strategies' => [
        'valuation' => [
            'FIFO' => FifoValuationStrategy::class,
            'LIFO' => LifoValuationStrategy::class,
            'WEIGHTED_AVERAGE' => WeightedAverageValuationStrategy::class,
            'MOVING_AVERAGE' => MovingAverageValuationStrategy::class,
            'STANDARD_COST' => StandardCostValuationStrategy::class,
            'SPECIFIC_IDENTIFICATION' => SpecificIdentificationValuationStrategy::class,
            'REPLACEMENT_COST' => ReplacementCostValuationStrategy::class,
        ],
        'allocation' => [
            'FIFO' => FifoAllocationStrategy::class,
            'LIFO' => LifoAllocationStrategy::class,
            'FEFO' => FefoAllocationStrategy::class,
            'BATCH' => BatchAllocationStrategy::class,
            'LOT' => LotAllocationStrategy::class,
            'SERIAL' => SerialAllocationStrategy::class,
            'LOCATION_PRIORITY' => LocationPriorityAllocationStrategy::class,
            'RESERVATION' => ReservationAllocationStrategy::class,
            'RULE_BASED' => RuleBasedAllocationStrategy::class,
        ],
    ],
];
