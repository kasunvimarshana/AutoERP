<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSupplierCategoryRequest extends FormRequest
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
            'code' => array_merge($required, ['string', 'max:50']),
            'name' => array_merge($required, ['string', 'max:120']),
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
