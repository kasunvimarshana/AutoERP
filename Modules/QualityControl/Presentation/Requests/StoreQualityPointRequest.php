<?php

namespace Modules\QualityControl\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityPointRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'product_id'     => 'nullable|string|uuid',
            'operation_type' => 'nullable|string|max:100',
            'team'           => 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ];
    }
}
