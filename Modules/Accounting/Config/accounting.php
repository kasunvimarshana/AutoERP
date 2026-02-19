<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Accounting Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Accounting module including chart of accounts,
    | journal entries, fiscal periods, and financial reporting.
    |
    */

    /**
     * Account code generation
     */
    'account_code_prefix' => env('ACCOUNTING_ACCOUNT_CODE_PREFIX', 'ACC-'),
    'account_code_length' => env('ACCOUNTING_ACCOUNT_CODE_LENGTH', 10),

    /**
     * Journal entry settings
     */
    'journal_entry_prefix' => env('ACCOUNTING_JOURNAL_ENTRY_PREFIX', 'JE-'),
    'allow_backdated_entries' => env('ACCOUNTING_ALLOW_BACKDATED_ENTRIES', false),
    'require_approval' => env('ACCOUNTING_REQUIRE_APPROVAL', true),

    /**
     * Fiscal period settings
     */
    'fiscal_year_start_month' => env('ACCOUNTING_FISCAL_YEAR_START_MONTH', 1), // January
    'allow_posting_to_closed_period' => env('ACCOUNTING_ALLOW_POSTING_TO_CLOSED_PERIOD', false),
    'auto_close_periods' => env('ACCOUNTING_AUTO_CLOSE_PERIODS', false),

    /**
     * Decimal precision
     */
    'decimal_scale' => env('ACCOUNTING_DECIMAL_SCALE', 6),
    'display_decimals' => env('ACCOUNTING_DISPLAY_DECIMALS', 2),

    /**
     * Financial statements
     */
    'statement_date_format' => env('ACCOUNTING_STATEMENT_DATE_FORMAT', 'Y-m-d'),
    'include_zero_balances' => env('ACCOUNTING_INCLUDE_ZERO_BALANCES', false),

    /**
     * Chart of accounts structure
     */
    'account_hierarchy_levels' => env('ACCOUNTING_HIERARCHY_LEVELS', 5),
    'account_types' => [
        'asset' => [
            'label' => 'Assets',
            'normal_balance' => 'debit',
            'categories' => [
                'current_assets' => 'Current Assets',
                'fixed_assets' => 'Fixed Assets',
                'other_assets' => 'Other Assets',
            ],
        ],
        'liability' => [
            'label' => 'Liabilities',
            'normal_balance' => 'credit',
            'categories' => [
                'current_liabilities' => 'Current Liabilities',
                'long_term_liabilities' => 'Long-term Liabilities',
            ],
        ],
        'equity' => [
            'label' => 'Equity',
            'normal_balance' => 'credit',
            'categories' => [
                'capital' => 'Capital',
                'retained_earnings' => 'Retained Earnings',
            ],
        ],
        'revenue' => [
            'label' => 'Revenue',
            'normal_balance' => 'credit',
            'categories' => [
                'sales_revenue' => 'Sales Revenue',
                'service_revenue' => 'Service Revenue',
                'other_revenue' => 'Other Revenue',
            ],
        ],
        'expense' => [
            'label' => 'Expenses',
            'normal_balance' => 'debit',
            'categories' => [
                'cost_of_goods_sold' => 'Cost of Goods Sold',
                'operating_expenses' => 'Operating Expenses',
                'other_expenses' => 'Other Expenses',
            ],
        ],
    ],

    /**
     * Integration settings
     */
    'auto_post_sales_invoices' => env('ACCOUNTING_AUTO_POST_SALES_INVOICES', true),
    'auto_post_purchase_bills' => env('ACCOUNTING_AUTO_POST_PURCHASE_BILLS', true),
    'auto_post_inventory_movements' => env('ACCOUNTING_AUTO_POST_INVENTORY_MOVEMENTS', true),

    /**
     * Reporting
     */
    'reporting' => [
        'trial_balance' => [
            'include_inactive' => false,
            'group_by_type' => true,
        ],
        'balance_sheet' => [
            'comparative_periods' => 3,
            'include_budgets' => false,
        ],
        'income_statement' => [
            'comparative_periods' => 3,
            'include_budgets' => false,
        ],
        'cash_flow' => [
            'method' => 'indirect', // 'direct' or 'indirect'
        ],
    ],
];
