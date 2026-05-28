<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPayrollRunRequest extends FormRequest
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
            'period_start' => array_merge($required, ['date']),
            'period_end' => array_merge($required, ['date']),
            'payment_date' => array_merge($required, ['date']),
            'status' => ['nullable', 'string', 'max:255'],
            'total_gross' => ['nullable', 'numeric'],
            'total_deductions' => ['nullable', 'numeric'],
            'total_net' => ['nullable', 'numeric'],
            'total_employer_contributions' => ['nullable', 'numeric'],
            'processed_at' => ['nullable', 'date'],
            'approved_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
