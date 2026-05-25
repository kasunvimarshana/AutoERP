<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertItemCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'parent_id' => ['nullable', 'integer', 'min:1', 'exists:item_categories,id'],
            'name' => array_merge($required, ['string', 'max:255']),
            'code' => ['nullable', 'string', 'max:255'],
            'depth' => ['nullable', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:0'],
            'updated_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
