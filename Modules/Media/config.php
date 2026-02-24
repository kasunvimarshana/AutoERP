<?php
return [
    'default_disk' => env('MEDIA_DISK', 'local'),
    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip', 'txt'],
    'max_size_mb' => env('MEDIA_MAX_SIZE_MB', 10),
];
