<?php

namespace Modules\Maintenance\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string'],
        ];
    }
}
