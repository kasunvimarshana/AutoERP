<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],
    'engines' => [
        'default_valuation_method' => 'weighted_average',
        'default_allocation_method' => 'fifo',
        'max_layer_fetch' => 1000,
        'max_stock_fetch' => 1000,
        'dimensions_priority' => [
            'tenant_id',
            'organization_unit_id',
            'warehouse_id',
            'location_id',
            'item_id',
            'variant_id',
            'batch_id',
            'lot_number',
            'serial_id',
        ],
        'valuation_strategy_map' => [
            'fifo' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FifoValuationStrategy::class,
            'lifo' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\LifoValuationStrategy::class,
            'weighted_average' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\WeightedAverageValuationStrategy::class,
            'standard' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\StandardCostValuationStrategy::class,
            'specific' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\SpecificValuationStrategy::class,
        ],
        'allocation_strategy_map' => [
            'fifo' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FifoAllocationStrategy::class,
            'fefo' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FefoAllocationStrategy::class,
            'proportional' => \Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\ProportionalAllocationStrategy::class,
        ],
    ],
];
