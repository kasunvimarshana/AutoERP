<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVehicleServiceLaborAssignmentRequest extends FormRequest
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
            'reference' => ['nullable', 'string', 'max:255'],
            'job_card_id' => array_merge($required, ['integer', 'min:1', 'exists:vehicle_service_job_cards,id']),
            'labor_item_id' => array_merge($required, ['integer', 'min:1', 'exists:vehicle_service_labor_items,id']),
            'employee_id' => array_merge($required, ['integer', 'min:1', 'exists:employees,id']),
            'hours_worked' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'incentive_type' => ['nullable', 'in:percentage,fixed'],
            'incentive_value' => ['nullable', 'numeric', 'min:0'],
            'role' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
