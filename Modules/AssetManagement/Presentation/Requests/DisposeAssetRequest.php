<?php

namespace Modules\AssetManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisposeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disposal_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_notes' => ['nullable', 'string'],
        ];
    }
}
