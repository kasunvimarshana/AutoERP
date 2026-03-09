<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->attributes->get('tenant_id') ?? $this->header('X-Tenant-ID');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'parent_id'   => ['nullable', 'uuid', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'metadata'    => ['nullable', 'array'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
            'image'       => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug must be lowercase alphanumeric with hyphens (e.g. "my-category").',
            'slug.unique' => 'A category with this slug already exists for your tenant.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->input('is_active', true),
        ]);

        if ($this->has('name') && !$this->has('slug')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->input('name')),
            ]);
        }
    }
}
