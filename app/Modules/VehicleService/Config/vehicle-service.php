<?php

declare(strict_types=1);

return [
    'documents' => [
        'allowed_types' => [
            'image',
            'inspection_report',
            'warranty',
            'invoice_copy',
            'other',
        ],
        'disk' => env('VEHICLE_SERVICE_DOCUMENT_DISK', 'tenant_private'),
        'max_size_kb' => (int) env('VEHICLE_SERVICE_DOCUMENT_MAX_SIZE_KB', 10240),
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
    ],
];
