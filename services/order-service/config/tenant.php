<?php

return [
    'header'           => env('TENANT_HEADER', 'X-Tenant-ID'),
    'fallback_to_user' => true,
    'model'            => \App\Models\Order::class,
];
