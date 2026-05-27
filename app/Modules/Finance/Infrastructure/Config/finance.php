<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],
    'core' => [
        'events' => [
            'generate_journal_from' => [],
        ],
        'fail_safe' => [
            'swallow_exceptions' => true,
        ],
    ],
];
