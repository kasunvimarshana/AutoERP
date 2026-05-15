<?php

return [
    'default_tenant_id' => (int) env('DOCUMENT_DEFAULT_TENANT_ID', 1),

    'attachments' => [
        'disk' => env('DOCUMENT_ATTACHMENT_DISK', 'local'),
        'directory' => env('DOCUMENT_ATTACHMENT_DIRECTORY', 'documents'),
        'max_size_kb' => (int) env('DOCUMENT_ATTACHMENT_MAX_SIZE_KB', 10240),
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    'default_status_transitions' => [
        'draft' => ['pending_approval', 'approved', 'void', 'cancelled'],
        'pending_approval' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['posted', 'cancelled'],
        'posted' => ['archived', 'void'],
        'rejected' => [],
        'void' => [],
        'cancelled' => [],
        'archived' => [],
    ],
];
