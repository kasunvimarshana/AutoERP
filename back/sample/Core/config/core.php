<?php

declare(strict_types=1);

return [
    'default_repository' => 'eloquent',
    'pagination' => [
        'per_page' => 15,
        'page_name' => 'page',
    ],
    'file_storage' => [
        /*
        |--------------------------------------------------------------------------
        | Default File Storage Disk
        |--------------------------------------------------------------------------
        |
        | This option defines the default disk that will be used by the
        | FileStorageService. It must be one of the disks configured in
        | your filesystems.php configuration file.
        |
        */
        'default_disk' => env('FILE_STORAGE_DISK', 'public'),
    ],
];
