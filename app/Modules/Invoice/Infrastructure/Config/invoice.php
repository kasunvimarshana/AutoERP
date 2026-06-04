<?php

declare(strict_types=1);

return [
    'directions' => [
        'payable',
        'receivable',
        'internal',
    ],

    'statuses' => [
        'draft',
        'approved',
        'posted',
        'partially_paid',
        'paid',
        'cancelled',
        'reversed',
    ],

    'line_types' => [
        'item',
        'service',
        'charge',
        'discount',
        'rounding',
        'note',
    ],

    'source_relation_types' => [
        'source',
        'adjustment_source',
        'reversal_source',
        'supporting_source',
    ],

    'link_types' => [
        'credit',
        'debit',
        'reversal',
        'correction',
        'consolidation',
        'split',
        'related',
    ],
];
