<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('attribute_groups', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['text', 'select', 'number', 'boolean'])],
            'is_required' => 'nullable|boolean',
        ];
    }
}
