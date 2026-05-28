<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSalaryStructureLineRequest extends FormRequest
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
            'salary_structure_id' => array_merge($required, ['integer', 'min:1', 'exists:salary_structures,id']),
            'salary_component_id' => array_merge($required, ['integer', 'min:1', 'exists:salary_components,id']),
            'calculation_type' => ['nullable', 'string', 'max:255'],
            'value' => array_merge($required, ['numeric']),
            'sequence' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
