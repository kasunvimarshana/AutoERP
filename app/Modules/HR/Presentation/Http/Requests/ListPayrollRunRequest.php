<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListPayrollRunRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('hr.pagination.max_per_page', 200)],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'payment_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
