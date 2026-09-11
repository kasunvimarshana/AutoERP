<?php

declare(strict_types=1);

return [
    'whatsapp' => [
        'default_country_calling_code' => env('DOCUMENT_SHARE_DEFAULT_COUNTRY_CALLING_CODE', '94'),
        'link_ttl_minutes' => (int) env('DOCUMENT_SHARE_LINK_TTL_MINUTES', 43200),
        'invoice_message' => "Hello {recipient_name},\n\nPlease find Invoice {document_number}.\nView or download the PDF: {document_url}",
        'purchase_order_message' => "Hello {recipient_name},\n\nPlease find Purchase Order {document_number}.\nView or download the PDF: {document_url}",
    ],
];
