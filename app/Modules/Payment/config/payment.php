<?php

declare(strict_types=1);

use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\CashRegisterRepositoryInterface;
use Modules\Payment\Application\Repositories\CheckRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentGroupRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;

return [
    'precision' => [
        'scale' => 4,
    ],

    'directions' => ['inbound', 'outbound'],

    'payment_method_types' => ['cash', 'bank_transfer', 'card', 'check', 'other'],

    'payment_statuses' => ['draft', 'posted', 'reconciled', 'voided'],

    'check_types' => ['inbound', 'outbound'],

    'check_statuses' => ['pending', 'deposited', 'cleared', 'bounced', 'cancelled'],

    'advance_types' => ['customer', 'supplier'],

    'advance_statuses' => ['open', 'partially_applied', 'fully_applied', 'refunded'],

    'allocation' => [
        'allow_over_allocation' => false,
        'allow_unallocated_posting' => true,
    ],

    'immutable' => [
        'payments' => [
            'status_column' => 'status',
            'statuses' => ['posted', 'reconciled', 'voided'],
        ],
        'payment_allocations' => [
            'after_create' => true,
        ],
        'advance_payment_allocations' => [
            'after_create' => true,
        ],
        'write_offs' => [
            'after_create' => true,
        ],
    ],

    'resources' => [
        'payment_methods' => [
            'repository' => PaymentMethodRepositoryInterface::class,
            'label' => 'Payment method',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'name' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'string'],
                'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'is_active' => ['nullable', 'boolean'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'payment_groups' => [
            'repository' => PaymentGroupRepositoryInterface::class,
            'label' => 'Payment group',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'transaction_number' => ['nullable', 'string', 'max:255'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'payments' => [
            'repository' => PaymentRepositoryInterface::class,
            'label' => 'Payment',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'party_type' => ['nullable', 'string', 'max:255'],
                'party_id' => ['nullable', 'integer'],
                'reference' => ['nullable', 'string', 'max:255'],
                'payment_number' => ['required', 'string', 'max:255'],
                'payment_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:0'],
                'direction' => ['nullable', 'string'],
                'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
                'account_id' => ['required', 'integer', 'exists:accounts,id'],
                'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
                'exchange_rate' => ['nullable', 'numeric', 'min:0'],
                'base_amount' => ['nullable', 'numeric', 'min:0'],
                'status' => ['nullable', 'string'],
                'notes' => ['nullable', 'string'],
                'idempotency_key' => ['nullable', 'string', 'max:255'],
                'journal_entry_id' => ['nullable', 'integer', 'exists:journal_entries,id'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'payment_allocations' => [
            'repository' => PaymentAllocationRepositoryInterface::class,
            'label' => 'Payment allocation',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'payment_id' => ['required', 'integer', 'exists:payments,id'],
                'document_type' => ['required', 'string', 'max:255'],
                'document_id' => ['required', 'integer'],
                'reference' => ['nullable', 'string', 'max:255'],
                'allocated_amount' => ['required', 'numeric', 'min:0'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'cash_registers' => [
            'repository' => CashRegisterRepositoryInterface::class,
            'label' => 'Cash register',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'name' => ['required', 'string', 'max:255'],
                'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
                'opening_balance' => ['nullable', 'numeric'],
                'current_balance' => ['nullable', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'checks' => [
            'repository' => CheckRepositoryInterface::class,
            'label' => 'Check',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'check_number' => ['required', 'string', 'max:255'],
                'type' => ['required', 'string'],
                'party_type' => ['nullable', 'string', 'max:255'],
                'party_id' => ['nullable', 'integer'],
                'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
                'check_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date'],
                'amount' => ['required', 'numeric', 'min:0'],
                'status' => ['nullable', 'string'],
                'clearance_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
                'created_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'advance_payments' => [
            'repository' => AdvancePaymentRepositoryInterface::class,
            'label' => 'Advance payment',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'party_type' => ['required', 'string', 'max:255'],
                'party_id' => ['required', 'integer'],
                'reference' => ['nullable', 'string', 'max:255'],
                'advance_number' => ['required', 'string', 'max:255'],
                'amount' => ['required', 'numeric', 'min:0'],
                'remaining_amount' => ['nullable', 'numeric', 'min:0'],
                'advance_date' => ['required', 'date'],
                'type' => ['nullable', 'string'],
                'status' => ['nullable', 'string'],
                'payment_id' => ['nullable', 'integer', 'exists:payments,id'],
                'notes' => ['nullable', 'string'],
                'created_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'advance_payment_allocations' => [
            'repository' => AdvancePaymentAllocationRepositoryInterface::class,
            'label' => 'Advance payment allocation',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'advance_payment_id' => ['required', 'integer', 'exists:advance_payments,id'],
                'document_type' => ['required', 'string', 'max:255'],
                'document_id' => ['required', 'integer'],
                'reference' => ['nullable', 'string', 'max:255'],
                'allocated_amount' => ['required', 'numeric', 'min:0'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'write_offs' => [
            'repository' => WriteOffRepositoryInterface::class,
            'label' => 'Write-off',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'document_type' => ['required', 'string', 'max:255'],
                'document_id' => ['required', 'integer'],
                'amount' => ['required', 'numeric', 'min:0'],
                'reason' => ['nullable', 'string', 'max:255'],
                'journal_entry_id' => ['nullable', 'integer', 'exists:journal_entries,id'],
                'reference' => ['nullable', 'string', 'max:255'],
                'created_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
    ],
];
