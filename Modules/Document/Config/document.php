<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document storage, uploads, and management
    |
    */

    'storage' => [
        /*
         * Storage disk for documents
         * Options: local, public, s3, etc.
         */
        'disk' => env('DOCUMENT_STORAGE_DISK', 'local'),

        /*
         * URL expiry time in minutes for temporary URLs
         */
        'url_expiry' => (int) env('DOCUMENT_URL_EXPIRY', 60),
    ],

    'upload' => [
        /*
         * Maximum file size in bytes (default: 10MB)
         */
        'max_size' => (int) env('DOCUMENT_MAX_SIZE', 10485760),

        /*
         * Allowed MIME types (empty array = all types allowed)
         */
        'allowed_mimes' => array_filter(explode(',', env('DOCUMENT_ALLOWED_MIMES', ''))),

        /*
         * Allowed file extensions (empty array = all extensions allowed)
         */
        'allowed_extensions' => array_filter(explode(',', env('DOCUMENT_ALLOWED_EXTENSIONS', ''))),

        /*
         * Enable virus scanning for uploads
         */
        'virus_scan_enabled' => (bool) env('DOCUMENT_VIRUS_SCAN', false),
    ],

    'versioning' => [
        /*
         * Enable automatic versioning
         */
        'enabled' => (bool) env('DOCUMENT_VERSIONING_ENABLED', true),

        /*
         * Maximum number of versions to keep per document
         * 0 = unlimited
         */
        'max_versions' => (int) env('DOCUMENT_MAX_VERSIONS', 0),

        /*
         * Auto-cleanup old versions after days
         * 0 = never cleanup
         */
        'cleanup_after_days' => (int) env('DOCUMENT_VERSION_CLEANUP_DAYS', 0),
    ],

    'search' => [
        /*
         * Enable full-text search
         */
        'full_text_enabled' => (bool) env('DOCUMENT_SEARCH_FULLTEXT', true),

        /*
         * Minimum search query length
         */
        'min_query_length' => (int) env('DOCUMENT_SEARCH_MIN_LENGTH', 3),
    ],

    'sharing' => [
        /*
         * Default share expiration in days
         * null = no expiration
         */
        'default_expiry_days' => env('DOCUMENT_SHARE_EXPIRY_DAYS', null),

        /*
         * Maximum share expiration in days
         * null = no limit
         */
        'max_expiry_days' => env('DOCUMENT_SHARE_MAX_EXPIRY_DAYS', 365),

        /*
         * Auto-cleanup expired shares
         */
        'auto_cleanup_enabled' => (bool) env('DOCUMENT_SHARE_AUTO_CLEANUP', true),
    ],

    'access_control' => [
        /*
         * Enable document access control
         */
        'enabled' => (bool) env('DOCUMENT_ACCESS_CONTROL', true),

        /*
         * Default access level for new documents
         * Options: private, shared, public
         */
        'default_access_level' => env('DOCUMENT_DEFAULT_ACCESS', 'private'),
    ],

    'metadata' => [
        /*
         * Extract metadata from uploads
         */
        'extract_enabled' => (bool) env('DOCUMENT_EXTRACT_METADATA', true),

        /*
         * Extract EXIF data from images
         */
        'extract_exif' => (bool) env('DOCUMENT_EXTRACT_EXIF', false),
    ],

    'preview' => [
        /*
         * Generate document previews/thumbnails
         */
        'enabled' => (bool) env('DOCUMENT_PREVIEW_ENABLED', false),

        /*
         * Preview image size
         */
        'thumbnail_size' => (int) env('DOCUMENT_THUMBNAIL_SIZE', 200),
    ],

    'activity' => [
        /*
         * Log document activities
         */
        'logging_enabled' => (bool) env('DOCUMENT_ACTIVITY_LOG', true),

        /*
         * Activities to log
         */
        'log_actions' => [
            'upload',
            'download',
            'view',
            'update',
            'delete',
            'share',
            'restore',
        ],

        /*
         * Cleanup activity logs after days
         */
        'cleanup_after_days' => (int) env('DOCUMENT_ACTIVITY_CLEANUP_DAYS', 365),
    ],

    'limits' => [
        /*
         * Maximum documents per folder
         * 0 = unlimited
         */
        'max_documents_per_folder' => (int) env('DOCUMENT_MAX_PER_FOLDER', 0),

        /*
         * Maximum storage per user in bytes
         * 0 = unlimited
         */
        'max_storage_per_user' => (int) env('DOCUMENT_MAX_STORAGE_USER', 0),

        /*
         * Maximum storage per tenant in bytes
         * 0 = unlimited
         */
        'max_storage_per_tenant' => (int) env('DOCUMENT_MAX_STORAGE_TENANT', 0),
    ],
];
