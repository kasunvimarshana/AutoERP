<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'field_label' => 'required|string|max:255',
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
            'sort_order' => 'integer|min:0',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'validation_rules' => 'nullable|string|max:500',
        ];
    }
}
