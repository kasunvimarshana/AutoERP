<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPerformanceReviewRequest extends FormRequest
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
            'employee_id' => array_merge($required, ['integer', 'min:1', 'exists:employees,id']),
            'cycle_id' => array_merge($required, ['integer', 'min:1', 'exists:performance_cycles,id']),
            'reviewer_id' => array_merge($required, ['integer', 'min:1', 'exists:users,id']),
            'overall_rating' => ['nullable', 'string', 'max:255'],
            'goals' => ['nullable', 'array'],
            'strengths' => ['nullable', 'string'],
            'improvements' => ['nullable', 'string'],
            'reviewer_comments' => ['nullable', 'string'],
            'employee_comments' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'acknowledged_at' => ['nullable', 'date'],
        ];
    }
}
