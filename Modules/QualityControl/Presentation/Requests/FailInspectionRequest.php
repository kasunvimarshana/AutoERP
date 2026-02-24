<?php

namespace Modules\QualityControl\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FailInspectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'qty_inspected' => 'nullable|numeric|min:0',
            'qty_failed'    => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
        ];
    }
}
