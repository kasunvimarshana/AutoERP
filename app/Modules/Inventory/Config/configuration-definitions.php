<?php

declare(strict_types=1);

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return [
    'inventory.valuation_method' => [
        'owner' => 'Inventory',
        'label' => 'Default valuation method',
        'description' => 'Fallback inventory valuation method when the item, category, and warehouse do not override it.',
        'type' => ConfigurationValueType::STRING,
        'default' => 'fifo',
        'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT, ConfigurationScope::ORGANIZATION_UNIT],
        'options' => ['fifo', 'weighted_average', 'standard', 'manual'],
        'runtime_mutable' => true,
        'sensitive' => false,
        'nullable' => false,
    ],
    'inventory.allocation_method' => [
        'owner' => 'Inventory',
        'label' => 'Default allocation method',
        'description' => 'Fallback stock allocation method when the item, category, and warehouse do not override it.',
        'type' => ConfigurationValueType::STRING,
        'default' => 'fifo',
        'scopes' => [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT, ConfigurationScope::ORGANIZATION_UNIT],
        'options' => ['fifo', 'fefo', 'batch', 'serial', 'manual'],
        'runtime_mutable' => true,
        'sensitive' => false,
        'nullable' => false,
    ],
];
