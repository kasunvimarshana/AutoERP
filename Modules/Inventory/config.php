<?php
return [
    'default_uom' => 'unit',
    'product_types' => ['physical', 'service', 'digital', 'bundle', 'consumable'],
    'movement_types' => ['receipt', 'delivery', 'transfer', 'adjustment', 'scrap'],
    'location_types' => [
        'receive', 'bulk_storage', 'pick_face', 'output',
        'scrap', 'transit', 'virtual', 'quality_control',
    ],
    'low_stock_check_enabled' => true,
];
