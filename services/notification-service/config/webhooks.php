<?php
declare(strict_types=1);
return [
    'timeout' => (int) env('WEBHOOK_TIMEOUT', 10),
    'max_retries' => (int) env('WEBHOOK_MAX_RETRIES', 3),
];
