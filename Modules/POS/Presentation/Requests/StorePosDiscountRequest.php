<?php

namespace Modules\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_\-]+$/i'],
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', 'string', 'in:percentage,fixed_amount'],
            'value'       => ['required', 'numeric', 'min:0.00000001'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at'  => ['nullable', 'date', 'after:now'],
            'is_active'   => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
