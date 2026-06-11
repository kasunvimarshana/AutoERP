<?php

declare(strict_types=1);

return [
    'calculation_methods' => [
        'percentage',
        'fixed',
        'inclusive',
        'exclusive',
        'compound',
    ],

    'exemption_statuses' => [
        'taxable',
        'exempt',
        'zero-rated',
        'suspended',
        'special_treatment',
    ],

    'posting_directions' => [
        'input',
        'output',
        'withholding',
        'tax',
    ],
];
