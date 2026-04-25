<?php

declare(strict_types=1);

return [
    'max_per_page' => 100,
    'low_stock_threshold' => '5.000000',
    'identifier_limit' => 5,
    'workflow_context_pricing_map' => [
        'buy' => 'purchase',
        'sell' => 'sales',
        'pos' => 'sales',
    ],
];
