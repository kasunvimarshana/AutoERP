<?php

namespace Modules\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closing_cash' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
