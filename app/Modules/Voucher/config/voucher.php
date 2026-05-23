<?php

declare(strict_types=1);

use Modules\Voucher\Application\Repositories\RecurringVoucherRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;

return [
    'precision' => [
        'scale' => 4,
    ],

    'types' => ['expense', 'income'],

    'sub_types' => [
        'electricity',
        'phone',
        'rent',
        'commission',
        'misc_income',
        'interest',
        'other',
    ],

    'statuses' => ['DRAFT', 'POSTED', 'PAID', 'VOID'],

    'frequencies' => ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],

    'immutable' => [
        'vouchers' => [
            'status_column' => 'status',
            'statuses' => ['POSTED', 'PAID', 'VOID'],
        ],
    ],

    'resources' => [
        'vouchers' => [
            'repository' => VoucherRepositoryInterface::class,
            'label' => 'Voucher',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'voucher_number' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'string'],
                'sub_type' => ['nullable', 'string'],
                'voucher_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:voucher_date'],
                'party_type' => ['nullable', 'string', 'max:255'],
                'party_id' => ['nullable', 'integer'],
                'reference' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'account_id' => ['required', 'integer', 'exists:accounts,id'],
                'contra_account_id' => ['required', 'integer', 'exists:accounts,id'],
                'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
                'tax_rate' => ['nullable', 'numeric', 'min:0'],
                'amount' => ['required', 'numeric', 'min:0'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
                'total_amount' => ['nullable', 'numeric', 'min:0'],
                'status' => ['nullable', 'string'],
                'created_by' => ['nullable', 'integer'],
                'updated_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'recurring_vouchers' => [
            'repository' => RecurringVoucherRepositoryInterface::class,
            'label' => 'Recurring voucher',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'name' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'string'],
                'sub_type' => ['nullable', 'string'],
                'party_type' => ['nullable', 'string', 'max:255'],
                'party_id' => ['nullable', 'integer'],
                'reference' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'account_id' => ['required', 'integer', 'exists:accounts,id'],
                'contra_account_id' => ['required', 'integer', 'exists:accounts,id'],
                'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
                'tax_rate' => ['nullable', 'numeric', 'min:0'],
                'amount' => ['required', 'numeric', 'min:0'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
                'total_amount' => ['nullable', 'numeric', 'min:0'],
                'frequency' => ['nullable', 'string'],
                'interval' => ['nullable', 'integer', 'min:1'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'next_run_date' => ['required', 'date'],
                'is_active' => ['nullable', 'boolean'],
                'created_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
    ],
];
