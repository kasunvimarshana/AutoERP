<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],
    'defaults' => [
        'status' => 'active',
    ],
    'status' => [
        'allowed' => [
            'active',
            'inactive',
            'blocked',
        ],
    ],
];
