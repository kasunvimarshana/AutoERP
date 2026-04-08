<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrgUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'parent_id'   => ['nullable', 'integer', 'min:1', 'exists:org_units,id'],
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50'],
            'type'        => ['required', 'string', 'in:company,division,department,branch,warehouse,store,other'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
