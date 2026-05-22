<?php

declare(strict_types=1);

use Modules\Inventory\Application\Rules\Allocation\PreferEarliestExpiryRule;
use Modules\Inventory\Application\Rules\Allocation\RequireUnexpiredBatchRule;
use Modules\Inventory\Application\Rules\Allocation\SkipZeroAvailableRule;
use Modules\Inventory\Application\Strategies\Allocation\BatchAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\LotAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\QuantityAllocationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\FifoValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\LifoValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\WeightedAverageValuationStrategy;
use Modules\Inventory\Domain\Enums\AllocationMethod;
use Modules\Inventory\Domain\Enums\ValuationMethod;

return [
    'valuation' => [
        'default_method' => env('INVENTORY_VALUATION_DEFAULT_METHOD', ValuationMethod::FIFO),
        'methods' => [
            ValuationMethod::FIFO => FifoValuationStrategy::class,
            ValuationMethod::LIFO => LifoValuationStrategy::class,
            ValuationMethod::WEIGHTED_AVERAGE => WeightedAverageValuationStrategy::class,
        ],
    ],

    'allocation' => [
        'default_method' => env('INVENTORY_ALLOCATION_DEFAULT_METHOD', AllocationMethod::QUANTITY),
        'methods' => [
            AllocationMethod::QUANTITY => QuantityAllocationStrategy::class,
            AllocationMethod::BATCH => BatchAllocationStrategy::class,
            AllocationMethod::LOT => LotAllocationStrategy::class,
        ],
        'rules' => [
            'skip_zero_available' => SkipZeroAvailableRule::class,
            'require_unexpired_batch' => RequireUnexpiredBatchRule::class,
            'prefer_earliest_expiry' => PreferEarliestExpiryRule::class,
        ],
        'default_rules' => [
            'skip_zero_available',
            'require_unexpired_batch',
            'prefer_earliest_expiry',
        ],
    ],
];
