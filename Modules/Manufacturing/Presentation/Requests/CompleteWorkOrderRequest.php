<?php

namespace Modules\Manufacturing\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_produced' => ['required', 'numeric', 'min:0'],
        ];
    }
}
