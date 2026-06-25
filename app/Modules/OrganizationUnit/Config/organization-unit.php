<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    'hierarchy' => [
        'maximum_path_length' => 1024,
    ],
    'storage' => [
        'disk' => env('ORGANIZATION_UNIT_STORAGE_DISK', env('TENANT_DOCUMENT_DISK', 'tenant_private')),
        'logo' => [
            'max_size_kb' => (int) env('ORGANIZATION_UNIT_LOGO_MAX_SIZE_KB', 5120),
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'documents' => [
            'max_size_kb' => (int) env('ORGANIZATION_UNIT_DOCUMENT_MAX_SIZE_KB', 10240),
            'allowed_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'text/plain',
                'text/csv',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ],
    ],
];
