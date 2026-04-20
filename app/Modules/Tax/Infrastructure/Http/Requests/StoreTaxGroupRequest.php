<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxGroupRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_groups', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => 'nullable|string',
        ];
    }
}
