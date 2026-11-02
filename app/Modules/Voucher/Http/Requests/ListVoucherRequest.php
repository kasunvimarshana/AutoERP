<?php

declare(strict_types=1);

namespace Modules\Voucher\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Voucher\Enums\VoucherType;

final class ListVoucherRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'voucher_type' => ['nullable', Rule::enum(VoucherType::class)],
            'source_module' => ['nullable', Rule::in(['Payment', 'Finance'])],
            'source_kind' => ['nullable', Rule::in(['payment', 'payment_reversal', 'finance_journal'])],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'document_status' => ['nullable', 'string', 'max:50'],
            'allocation_status' => ['nullable', 'string', 'max:50'],
            'posting_status' => ['nullable', 'string', 'max:50'],
            'instrument_status' => ['nullable', 'string', 'max:50'],
            'party' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'amount_min' => ['nullable', 'decimal:0,6'],
            'amount_max' => ['nullable', 'decimal:0,6'],
            'sort' => ['nullable', Rule::in(['voucher_date', 'voucher_number', 'voucher_type', 'amount', 'document_status', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
