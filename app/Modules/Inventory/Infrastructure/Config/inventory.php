<?php

declare(strict_types=1);

use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\ConfigurableSequenceAllocationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FefoAllocationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FifoAllocationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FifoValuationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\LifoValuationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\ProportionalAllocationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\SpecificValuationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\StandardCostValuationStrategy;
use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\WeightedAverageValuationStrategy;
use Modules\Inventory\Domain\Constants\InventoryAllocationMethod;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

$inventoryDimensions = InventoryDimension::all();

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],
    'engines' => [
        'precision' => 4,
        'quantity_field' => 'quantity',
        'required_dimensions' => [
            InventoryDimension::TENANT_ID,
            InventoryDimension::ITEM_ID,
        ],
        'criteria_dimensions' => $inventoryDimensions,
        'default_valuation_method' => InventoryValuationMethod::WEIGHTED_AVERAGE,
        'default_allocation_method' => InventoryAllocationMethod::FIFO,
        'max_layer_fetch' => 1000,
        'max_stock_fetch' => 1000,
        'dimensions_priority' => $inventoryDimensions,
        'valuation_strategy_map' => [
            InventoryValuationMethod::FIFO => FifoValuationStrategy::class,
            InventoryValuationMethod::LIFO => LifoValuationStrategy::class,
            InventoryValuationMethod::WEIGHTED_AVERAGE => WeightedAverageValuationStrategy::class,
            InventoryValuationMethod::STANDARD => StandardCostValuationStrategy::class,
            InventoryValuationMethod::SPECIFIC => SpecificValuationStrategy::class,
        ],
        'allocation_strategy_map' => [
            InventoryAllocationMethod::FIFO => FifoAllocationStrategy::class,
            InventoryAllocationMethod::FEFO => FefoAllocationStrategy::class,
            InventoryAllocationMethod::PROPORTIONAL => ProportionalAllocationStrategy::class,
            'batch' => [
                'class' => ConfigurableSequenceAllocationStrategy::class,
                'parameters' => ['method' => 'batch'],
            ],
            'lot' => [
                'class' => ConfigurableSequenceAllocationStrategy::class,
                'parameters' => ['method' => 'lot'],
            ],
            'serial' => [
                'class' => ConfigurableSequenceAllocationStrategy::class,
                'parameters' => ['method' => 'serial'],
            ],
            'priority' => [
                'class' => ConfigurableSequenceAllocationStrategy::class,
                'parameters' => ['method' => 'priority'],
            ],
            'location' => [
                'class' => ConfigurableSequenceAllocationStrategy::class,
                'parameters' => ['method' => 'location'],
            ],
            'warehouse' => [
                'class' => ConfigurableSequenceAllocationStrategy::class,
                'parameters' => ['method' => 'warehouse'],
            ],
        ],
        'valuation' => [
            'default_method' => InventoryValuationMethod::WEIGHTED_AVERAGE,
            'method_field' => 'valuation_method',
            'criteria_dimensions' => $inventoryDimensions,
            'configuration_dimensions' => [
                InventoryDimension::TENANT_ID,
                InventoryDimension::ORGANIZATION_UNIT_ID,
                InventoryDimension::WAREHOUSE_ID,
                InventoryDimension::LOCATION_ID,
                InventoryDimension::ITEM_ID,
                InventoryDimension::VARIANT_ID,
                InventoryDimension::BATCH_ID,
                InventoryDimension::SERIAL_ID,
            ],
            'source_limits' => [
                'layers' => 1000,
                'stock_levels' => 1000,
            ],
            'strategy_map' => [],
            'policies' => [],
            'methods' => [
                InventoryValuationMethod::FIFO => [
                    'ordering' => [
                        ['column' => 'inventory_cost_layers.layer_date', 'direction' => 'asc'],
                        ['column' => 'inventory_cost_layers.id', 'direction' => 'asc'],
                    ],
                ],
                InventoryValuationMethod::LIFO => [
                    'ordering' => [
                        ['column' => 'inventory_cost_layers.layer_date', 'direction' => 'desc'],
                        ['column' => 'inventory_cost_layers.id', 'direction' => 'desc'],
                    ],
                ],
                InventoryValuationMethod::WEIGHTED_AVERAGE => [
                    'ordering' => [
                        ['column' => 'inventory_cost_layers.layer_date', 'direction' => 'asc'],
                        ['column' => 'inventory_cost_layers.id', 'direction' => 'asc'],
                    ],
                ],
                InventoryValuationMethod::STANDARD => [
                    'ordering' => [
                        ['column' => 'inventory_cost_layers.layer_date', 'direction' => 'asc'],
                        ['column' => 'inventory_cost_layers.id', 'direction' => 'asc'],
                    ],
                ],
                InventoryValuationMethod::SPECIFIC => [
                    'ordering' => [
                        ['column' => 'inventory_cost_layers.id', 'direction' => 'asc'],
                    ],
                ],
                'default' => [
                    'ordering' => [
                        ['column' => 'inventory_cost_layers.id', 'direction' => 'asc'],
                    ],
                ],
            ],
        ],
        'allocation' => [
            'default_method' => InventoryAllocationMethod::FIFO,
            'method_field' => 'allocation_method',
            'criteria_dimensions' => $inventoryDimensions,
            'allocation_dimensions' => $inventoryDimensions,
            'source_limits' => [
                'stock_levels' => 1000,
            ],
            'strategy_map' => [],
            'policies' => [],
            'methods' => [
                InventoryAllocationMethod::FIFO => [
                    'ordering' => [
                        ['column' => 'stock_levels.last_movement_at', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                InventoryAllocationMethod::FEFO => [
                    'joins' => [
                        [
                            'type' => 'left',
                            'table' => 'batches',
                            'first' => 'stock_levels.batch_id',
                            'operator' => '=',
                            'second' => 'batches.id',
                        ],
                    ],
                    'ordering' => [
                        ['column' => 'batches.expiry_date', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                InventoryAllocationMethod::PROPORTIONAL => [
                    'ordering' => [
                        ['column' => 'stock_levels.last_movement_at', 'direction' => 'desc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'desc'],
                    ],
                ],
                'batch' => [
                    'joins' => [
                        [
                            'type' => 'left',
                            'table' => 'batches',
                            'first' => 'stock_levels.batch_id',
                            'operator' => '=',
                            'second' => 'batches.id',
                        ],
                    ],
                    'ordering' => [
                        ['column' => 'batches.received_date', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'batches.batch_number', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                'lot' => [
                    'joins' => [
                        [
                            'type' => 'left',
                            'table' => 'batches',
                            'first' => 'stock_levels.batch_id',
                            'operator' => '=',
                            'second' => 'batches.id',
                        ],
                    ],
                    'ordering' => [
                        ['column' => 'batches.lot_number', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                'serial' => [
                    'joins' => [
                        [
                            'type' => 'left',
                            'table' => 'serials',
                            'first' => 'stock_levels.serial_id',
                            'operator' => '=',
                            'second' => 'serials.id',
                        ],
                    ],
                    'ordering' => [
                        ['column' => 'serials.serial_number', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                'priority' => [
                    'ordering' => [
                        ['column' => 'stock_levels.quantity_reserved', 'direction' => 'desc'],
                        ['column' => 'stock_levels.last_movement_at', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                'location' => [
                    'ordering' => [
                        ['column' => 'stock_levels.location_id', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                'warehouse' => [
                    'ordering' => [
                        ['column' => 'stock_levels.warehouse_id', 'direction' => 'asc'],
                        ['column' => 'stock_levels.location_id', 'direction' => 'asc', 'nulls_last' => true],
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
                'default' => [
                    'ordering' => [
                        ['column' => 'stock_levels.id', 'direction' => 'asc'],
                    ],
                ],
            ],
        ],
    ],
];
