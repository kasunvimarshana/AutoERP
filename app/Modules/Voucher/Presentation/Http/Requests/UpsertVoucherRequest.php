<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('voucher');

        return [
            'tenant_id' => [$id === null ? 'required' : 'sometimes', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'voucher_type_id' => [$id === null ? 'required' : 'sometimes', 'integer', 'min:1'],
            'voucher_number' => ['nullable', 'string', 'max:120'],
            'voucher_date' => [$id === null ? 'required' : 'sometimes', 'date'],
            'party_type' => ['nullable', 'string', 'max:120'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'direction' => ['nullable', 'string', 'max:40'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'payment_method_id' => ['nullable', 'integer', 'min:1'],
            'cash_account_id' => ['nullable', 'integer', 'min:1'],
            'bank_account_id' => ['nullable', 'integer', 'min:1'],
            'reference_type' => ['nullable', 'string', 'max:120'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'lines' => ['nullable', 'array', 'min:2'],
            'lines.*.account_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.credit_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.currency_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_rate_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.metadata' => ['nullable', 'array'],
        ];
    }
}
