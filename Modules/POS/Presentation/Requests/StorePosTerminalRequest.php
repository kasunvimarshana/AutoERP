<?php

namespace Modules\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'location_id'     => ['nullable', 'uuid'],
            'is_active'       => ['sometimes', 'boolean'],
            'opening_balance' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
