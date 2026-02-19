<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Purchase Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Purchase module including vendor management,
    | purchase orders, goods receipts, bills, and payments.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Code Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes used for auto-generating codes for various purchase documents.
    |
    */
    'vendor_code_prefix' => env('PURCHASE_VENDOR_CODE_PREFIX', 'VEN-'),
    'po_code_prefix' => env('PURCHASE_PO_CODE_PREFIX', 'PO-'),
    'gr_code_prefix' => env('PURCHASE_GR_CODE_PREFIX', 'GR-'),
    'bill_code_prefix' => env('PURCHASE_BILL_CODE_PREFIX', 'BILL-'),
    'payment_code_prefix' => env('PURCHASE_PAYMENT_CODE_PREFIX', 'PAY-'),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Terms
    |--------------------------------------------------------------------------
    |
    | Default payment terms in days for new vendors.
    |
    */
    'default_payment_terms' => (int) env('PURCHASE_DEFAULT_PAYMENT_TERMS', 30),

    /*
    |--------------------------------------------------------------------------
    | Purchase Order Settings
    |--------------------------------------------------------------------------
    |
    | Settings for purchase order management and approval workflows.
    |
    */
    'po_requires_approval' => env('PURCHASE_PO_REQUIRES_APPROVAL', true),
    'po_approval_threshold' => env('PURCHASE_PO_APPROVAL_THRESHOLD', 10000),
    'po_auto_send_on_approval' => env('PURCHASE_PO_AUTO_SEND_ON_APPROVAL', false),

    /*
    |--------------------------------------------------------------------------
    | Goods Receipt Settings
    |--------------------------------------------------------------------------
    |
    | Settings for goods receipt processing and inventory posting.
    |
    */
    'gr_auto_post_to_inventory' => env('PURCHASE_GR_AUTO_POST_TO_INVENTORY', false),
    'gr_allow_over_receipt' => env('PURCHASE_GR_ALLOW_OVER_RECEIPT', false),
    'gr_over_receipt_tolerance_percent' => (float) env('PURCHASE_GR_OVER_RECEIPT_TOLERANCE', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Bill Settings
    |--------------------------------------------------------------------------
    |
    | Settings for vendor bill management and payment processing.
    |
    */
    'bill_default_due_days' => (int) env('PURCHASE_BILL_DEFAULT_DUE_DAYS', 30),
    'bill_overdue_alert_days' => (int) env('PURCHASE_BILL_OVERDUE_ALERT_DAYS', 7),
    'bill_requires_gr' => env('PURCHASE_BILL_REQUIRES_GR', false),
    'bill_allow_partial_payment' => env('PURCHASE_BILL_ALLOW_PARTIAL_PAYMENT', true),

    /*
    |--------------------------------------------------------------------------
    | 3-Way Matching
    |--------------------------------------------------------------------------
    |
    | Enable 3-way matching between PO, GR, and Bill.
    |
    */
    '3way_matching_enabled' => env('PURCHASE_3WAY_MATCHING_ENABLED', false),
    '3way_matching_tolerance_percent' => (float) env('PURCHASE_3WAY_MATCHING_TOLERANCE', 2.0),

    /*
    |--------------------------------------------------------------------------
    | Vendor Settings
    |--------------------------------------------------------------------------
    |
    | Settings for vendor management and credit control.
    |
    */
    'vendor_credit_limit_check' => env('PURCHASE_VENDOR_CREDIT_LIMIT_CHECK', true),
    'vendor_auto_block_on_limit' => env('PURCHASE_VENDOR_AUTO_BLOCK_ON_LIMIT', false),

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    |
    | Enable audit logging for purchase transactions.
    |
    */
    'audit_enabled' => env('PURCHASE_AUDIT_ENABLED', true),
    'audit_async' => env('PURCHASE_AUDIT_ASYNC', true),
];
