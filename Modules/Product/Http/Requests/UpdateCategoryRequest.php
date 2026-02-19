<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Category Request
 */
class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product_category'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $category = $this->route('product_category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('product_categories', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($category->id)
                    ->whereNull('deleted_at'),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
                'different:id',
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'parent_id.different' => 'A category cannot be its own parent.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'category name',
            'code' => 'category code',
            'parent_id' => 'parent category',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->parent_id) {
                $this->validateNonCircular($validator);
            }
        });
    }

    /**
     * Validate that setting this parent doesn't create a circular reference
     */
    private function validateNonCircular($validator): void
    {
        $category = $this->route('product_category');
        $parentId = $this->parent_id;
        $visited = [];

        while ($parentId !== null) {
            if ($parentId === $category->id) {
                $validator->errors()->add(
                    'parent_id',
                    'Setting this parent would create a circular reference.'
                );

                return;
            }

            if (isset($visited[$parentId])) {
                break;
            }

            $visited[$parentId] = true;
            $parent = \Modules\Product\Models\ProductCategory::find($parentId);
            $parentId = $parent ? $parent->parent_id : null;
        }
    }
}
