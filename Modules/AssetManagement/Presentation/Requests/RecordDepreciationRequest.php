<?php

namespace Modules\AssetManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordDepreciationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'depreciation_amount' => ['required', 'numeric', 'min:0.00000001'],
            'period_label'        => ['nullable', 'string', 'max:100'],
        ];
    }
}
