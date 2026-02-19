<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sales Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sales quotations, orders, and invoices.
    | All values use environment variables with sensible defaults.
    |
    */

    'quotation' => [
        /*
         * Quotation code prefix
         */
        'prefix' => env('SALES_QUOTATION_PREFIX', 'QUO-'),

        /*
         * Quotation code length (including prefix)
         */
        'code_length' => env('SALES_QUOTATION_CODE_LENGTH', 10),

        /*
         * Default validity period in days
         */
        'default_validity_days' => (int) env('SALES_QUOTATION_VALIDITY', 30),

        /*
         * Auto-expire quotations after valid_until date
         */
        'auto_expire' => (bool) env('SALES_QUOTATION_AUTO_EXPIRE', true),
    ],

    'order' => [
        /*
         * Order code prefix
         */
        'prefix' => env('SALES_ORDER_PREFIX', 'ORD-'),

        /*
         * Order code length (including prefix)
         */
        'code_length' => env('SALES_ORDER_CODE_LENGTH', 10),

        /*
         * Reserve stock when order is confirmed
         */
        'reserve_stock' => (bool) env('SALES_ORDER_RESERVE_STOCK', true),

        /*
         * Auto-confirm orders on creation
         */
        'auto_confirm' => (bool) env('SALES_ORDER_AUTO_CONFIRM', false),
    ],

    'invoice' => [
        /*
         * Invoice code prefix
         */
        'prefix' => env('SALES_INVOICE_PREFIX', 'INV-'),

        /*
         * Invoice code length (including prefix)
         */
        'code_length' => env('SALES_INVOICE_CODE_LENGTH', 10),

        /*
         * Default payment terms in days
         */
        'default_payment_terms' => (int) env('SALES_INVOICE_PAYMENT_TERMS', 30),

        /*
         * Auto-mark invoices as overdue after due date
         */
        'auto_overdue' => (bool) env('SALES_INVOICE_AUTO_OVERDUE', true),

        /*
         * Send reminder before due date (days)
         */
        'reminder_days_before_due' => (int) env('SALES_INVOICE_REMINDER_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | Precision for financial calculations using BCMath
    |
    */

    'decimal_scale' => (int) env('SALES_DECIMAL_SCALE', 6),
    'display_decimals' => (int) env('SALES_DISPLAY_DECIMALS', 2),

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    */

    'tax' => [
        /*
         * Default tax rate (percentage)
         */
        'default_rate' => (float) env('SALES_TAX_DEFAULT_RATE', 0.0),

        /*
         * Tax inclusive pricing
         */
        'tax_inclusive' => (bool) env('SALES_TAX_INCLUSIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discount Configuration
    |--------------------------------------------------------------------------
    */

    'discount' => [
        /*
         * Maximum discount percentage allowed
         */
        'max_percentage' => (float) env('SALES_MAX_DISCOUNT_PERCENTAGE', 100.0),

        /*
         * Require approval for discounts above threshold
         */
        'approval_threshold' => (float) env('SALES_DISCOUNT_APPROVAL_THRESHOLD', 10.0),
    ],
];
