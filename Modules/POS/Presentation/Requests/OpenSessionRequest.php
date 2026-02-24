<?php

namespace Modules\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'terminal_id'  => ['required', 'uuid'],
            'cashier_id'   => ['nullable', 'uuid'],
            'opening_cash' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
