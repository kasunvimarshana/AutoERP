<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customization\Domain\Enums\CustomFieldType;

class CreateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $types = array_map(fn ($c) => $c->value, CustomFieldType::cases());

        return [
            'tenant_id' => 'required|integer|min:1',
            'entity_type' => 'required|string|max:100',
            'field_key' => 'required|string|max:100|alpha_dash',
            'field_label' => 'required|string|max:255',
            'field_type' => 'required|string|in:' . implode(',', $types),
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
            'sort_order' => 'integer|min:0',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'validation_rules' => 'nullable|string|max:500',
        ];
    }
}
