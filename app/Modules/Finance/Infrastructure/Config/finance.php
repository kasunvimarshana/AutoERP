<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],
    'posting_accounts' => [
        'accounts_receivable' => env('FINANCE_ACCOUNT_AR', '1100'),
        'accounts_payable' => env('FINANCE_ACCOUNT_AP', '2000'),
        'sales_income' => env('FINANCE_ACCOUNT_SALES_INCOME', '4000'),
        'purchase_expense' => env('FINANCE_ACCOUNT_PURCHASE_EXPENSE', '5000'),
        'tax' => env('FINANCE_ACCOUNT_TAX', '2100'),
    ],
];
