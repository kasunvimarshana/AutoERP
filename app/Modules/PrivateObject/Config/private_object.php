<?php

declare(strict_types=1);

return [
    'default_disk' => env('PRIVATE_OBJECT_DISK', env('FILESYSTEM_DISK', 'local')),
];
