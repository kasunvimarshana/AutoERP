<?php

declare(strict_types=1);

return [
    'file_storage' => [
        'default_disk' => env('CORE_FILE_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    ],
    'slug' => [
        'fallback' => env('CORE_SLUG_FALLBACK', 'n-a'),
    ],
];
