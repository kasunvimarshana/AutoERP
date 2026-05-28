<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVoucherLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'min:1'],
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
