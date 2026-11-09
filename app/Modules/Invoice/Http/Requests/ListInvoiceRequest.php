<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;

final class ListInvoiceRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach ([
            'search', 'invoice_type', 'direction', 'status', 'party_id',
            'currency_id', 'date_from', 'date_to', 'balance_status',
            'settlement_eligible', 'page', 'per_page',
        ] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'invoice_type' => ['nullable', Rule::enum(InvoiceType::class)],
            'direction' => ['nullable', Rule::enum(InvoiceDirection::class)],
            'status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('currencies', 'id'),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'balance_status' => ['nullable', Rule::enum(InvoiceBalanceStatus::class)],
            'settlement_eligible' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
