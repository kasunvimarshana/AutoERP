<?php

declare(strict_types=1);

return [
    'whatsapp' => [
        'default_country_calling_code' => env('DOCUMENT_SHARE_DEFAULT_COUNTRY_CALLING_CODE', '94'),
        'link_ttl_minutes' => (int) env('DOCUMENT_SHARE_LINK_TTL_MINUTES', 43200),
        'invoice_message' => "Hello {recipient_name},\n\n*Invoice {document_number}*\n\n*Invoice total:* {invoice_total}\n*Paid amount:* {paid_amount}\n*Balance due:* {balance_due}\n\n*View or download PDF:*\n{document_url}",
        'purchase_order_message' => "Hello {recipient_name},\n\n*Purchase Order {document_number}*\n\n*View or download PDF:*\n{document_url}",
    ],
];
