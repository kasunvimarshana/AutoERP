<?php

declare(strict_types=1);

return [
    'billing_timezone' => env(
        'VEHICLE_RENTAL_BILLING_TIMEZONE',
        env('APP_TIMEZONE', 'UTC'),
    ),
];
