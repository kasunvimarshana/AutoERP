<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VoucherWorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'acted_by' => ['nullable', 'integer', 'min:1'],
            'posted_by' => ['nullable', 'integer', 'min:1'],
            'comments' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
            'voucher_date' => ['nullable', 'date'],
        ];
    }
}
