<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCustomFieldValuesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|min:1',
            'entity_type' => 'required|string|max:100',
            'entity_id' => 'required|integer|min:1',
            'values' => 'required|array',
            'values.*.field_id' => 'required|integer|min:1',
            'values.*.value' => 'nullable|string',
        ];
    }
}
