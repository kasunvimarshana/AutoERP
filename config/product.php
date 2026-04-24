<?php

return [
    'search_projection' => [
        // Delay queue dispatch to coalesce bursty writes for same tenant/product.
        'debounce_seconds' => (int) env('PRODUCT_SEARCH_PROJECTION_DEBOUNCE_SECONDS', 2),
    ],
];
