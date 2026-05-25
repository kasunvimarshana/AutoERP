<?php

declare(strict_types=1);

return [
    'guard' => env('USER_MODULE_GUARD', (string) config('auth.defaults.guard', 'api')),
];
