<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Gateway Configuration
    |--------------------------------------------------------------------------
    */

    // Requests per IP per minute before 429 is returned
    'rate_limit_per_minute' => (int) env('RATE_LIMIT_PER_MINUTE', 60),
];
