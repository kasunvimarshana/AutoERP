<?php

declare(strict_types=1);

use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;

return [
    'precision' => [
        'scale' => 4,
    ],

    'directions' => ['inbound', 'outbound'],

    'statuses' => ['draft', 'approved', 'partially_paid', 'paid', 'disputed', 'cancelled'],

    'invoice_types' => [
        'purchase',
        'sale',
        'vehicle_service',
        'vehicle_rental_lessor',
        'vehicle_rental_lessee',
        'generic',
    ],

    'discount_types' => ['fixed', 'percentage'],

    'calculation' => [
        'header_discount_base' => 'net_after_line_discount',
        'recalculate_status_from_paid_amount' => true,
        'manual_statuses' => ['disputed', 'cancelled'],
    ],

    'immutable' => [
        'invoices' => [
            'status_column' => 'status',
            'statuses' => ['paid', 'cancelled'],
        ],
    ],

    'resources' => [
        'invoices' => [
            'repository' => InvoiceRepositoryInterface::class,
            'label' => 'Invoice',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'direction' => ['nullable', 'string'],
                'invoice_type' => ['required', 'string'],
                'invoice_number' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'string'],
                'party_type' => ['nullable', 'string', 'max:255'],
                'party_id' => ['nullable', 'integer'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
                'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
                'exchange_rate' => ['nullable', 'numeric', 'min:0'],
                'header_discount_type' => ['nullable', 'string'],
                'header_discount_value' => ['nullable', 'numeric', 'min:0'],
                'header_discount_amount' => ['nullable', 'numeric', 'min:0'],
                'header_tax_group_id' => ['nullable', 'integer', 'exists:tax_groups,id'],
                'header_tax_amount' => ['nullable', 'numeric', 'min:0'],
                'debit_note_total' => ['nullable', 'numeric', 'min:0'],
                'credit_note_total' => ['nullable', 'numeric', 'min:0'],
                'paid_amount' => ['nullable', 'numeric', 'min:0'],
                'ap_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'ar_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'journal_entry_id' => ['nullable', 'integer', 'exists:journal_entries,id'],
                'notes' => ['nullable', 'string'],
                'created_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'references' => [
            'repository' => InvoiceReferenceRepositoryInterface::class,
            'label' => 'Invoice reference',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
                'reference' => ['nullable', 'string', 'max:255'],
                'document_type' => ['required', 'string', 'max:255'],
                'document_id' => ['required', 'integer'],
                'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
                'exchange_rate' => ['nullable', 'numeric', 'min:0'],
                'header_discount_type' => ['nullable', 'string'],
                'header_discount_value' => ['nullable', 'numeric', 'min:0'],
                'header_discount_amount' => ['nullable', 'numeric', 'min:0'],
                'header_tax_group_id' => ['nullable', 'integer', 'exists:tax_groups,id'],
                'header_tax_amount' => ['nullable', 'numeric', 'min:0'],
                'debit_note_total' => ['nullable', 'numeric', 'min:0'],
                'credit_note_total' => ['nullable', 'numeric', 'min:0'],
                'paid_amount' => ['nullable', 'numeric', 'min:0'],
                'ap_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'ar_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'journal_entry_id' => ['nullable', 'integer', 'exists:journal_entries,id'],
                'notes' => ['nullable', 'string'],
                'created_by' => ['nullable', 'integer'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
        'lines' => [
            'repository' => InvoiceLineRepositoryInterface::class,
            'label' => 'Invoice line',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
                'invoice_reference_id' => ['nullable', 'integer', 'exists:invoice_references,id'],
                'reference' => ['nullable', 'string', 'max:255'],
                'item_type' => ['nullable', 'string', 'max:255'],
                'item_id' => ['nullable', 'integer'],
                'description' => ['nullable', 'string'],
                'uom_id' => ['nullable', 'integer', 'exists:unit_of_measures,id'],
                'quantity' => ['required', 'numeric', 'min:0'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'discount_type' => ['nullable', 'string'],
                'discount_value' => ['nullable', 'numeric', 'min:0'],
                'discount_amount' => ['nullable', 'numeric', 'min:0'],
                'tax_group_id' => ['nullable', 'integer', 'exists:tax_groups,id'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
                'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'metadata' => ['nullable', 'array'],
            ],
        ],
    ],
];
