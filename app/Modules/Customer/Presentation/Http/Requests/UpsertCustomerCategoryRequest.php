<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCustomerCategoryRequest extends FormRequest
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
            'category_code' => array_merge($required, ['string', 'max:50']),
            'category_name' => array_merge($required, ['string', 'max:120']),
            'parent_id' => ['nullable', 'integer', 'min:1', 'exists:customer_categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
