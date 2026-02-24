<?php
return [
    'default_currency' => 'USD',
    'default_payment_terms' => 'net_30',
    'quotation_validity_days' => 30,
    'order_statuses' => [
        'draft', 'confirmed', 'processing',
        'partially_shipped', 'shipped', 'invoiced', 'closed', 'cancelled',
    ],
];
