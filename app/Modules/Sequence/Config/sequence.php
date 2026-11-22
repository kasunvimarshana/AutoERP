<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],
    'defaults' => [
        'prefix' => '',
        'suffix' => '',
        'padding' => 5,
        'next_number' => 1,
        'period_type' => 'yearly',
    ],
    'tokens' => [
        'supported' => [
            'TENANT_ID',
            'ORG_ID',
            'DOC_TYPE',
            'PERIOD',
        ],
    ],
];
