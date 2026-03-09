<?php

return [
    'secret' => env('WEBHOOK_SECRET', ''),
    'timeout' => env('WEBHOOK_TIMEOUT', 10),
    'max_attempts' => env('WEBHOOK_MAX_ATTEMPTS', 3),
];
