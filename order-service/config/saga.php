<?php

return [
    'timeout'      => (int) env('SAGA_TIMEOUT', 300),
    'redis_prefix' => 'saga:',
    'steps'        => ['RESERVE_INVENTORY', 'PROCESS_PAYMENT', 'SEND_NOTIFICATION'],
];
