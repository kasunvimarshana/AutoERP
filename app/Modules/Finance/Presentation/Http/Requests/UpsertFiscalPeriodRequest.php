<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertFiscalPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'fiscal_year_id' => ['required', 'integer', 'min:1', 'exists:fiscal_years,id', ],
            'period_number' => ['required', 'integer', 'min:1', ],
            'name' => ['required', 'string', 'max:255', ],
            'start_date' => ['required', 'date', ],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', ],
            'status' => ['nullable', 'string', 'max:255', ],
            'period_type' => ['nullable', 'string', 'max:255', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
        ];
    }
}
