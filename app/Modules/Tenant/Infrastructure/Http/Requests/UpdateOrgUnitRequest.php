<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrgUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('org_unit');

        return [
            'parent_id'   => ['nullable', 'integer', 'min:1', 'exists:org_units,id', Rule::notIn([$id])],
            'name'        => ['sometimes', 'string', 'max:255'],
            'code'        => ['sometimes', 'string', 'max:50'],
            'type'        => ['sometimes', 'string', 'in:company,division,department,branch,warehouse,store,other'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
