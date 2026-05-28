<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVoucherAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'voucher_line_id' => ['nullable', 'integer', 'min:1'],
            'target_type' => ['required', 'string', 'max:120'],
            'target_id' => ['required', 'integer', 'min:1'],
            'allocated_amount' => ['required', 'numeric', 'gt:0'],
            'status' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
