<?php

namespace Modules\POS\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }
}
