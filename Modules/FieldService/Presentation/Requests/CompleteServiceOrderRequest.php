<?php

namespace Modules\FieldService\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteServiceOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'duration_hours'   => 'nullable|numeric|min:0',
            'labor_cost'       => 'nullable|numeric|min:0',
            'parts_cost'       => 'nullable|numeric|min:0',
            'resolution_notes' => 'nullable|string',
        ];
    }
}
