<?php

namespace Modules\Manufacturing\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bom_id'           => ['required', 'uuid'],
            'quantity_planned'  => ['required', 'numeric', 'min:0.001'],
            'scheduled_start'  => ['nullable', 'date'],
            'scheduled_end'    => ['nullable', 'date'],
        ];
    }
}
