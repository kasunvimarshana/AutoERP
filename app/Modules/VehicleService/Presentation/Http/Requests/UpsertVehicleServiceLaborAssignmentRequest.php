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
            'tenant_id' => [...$required, 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'job_card_id' => [...$required, 'integer', 'min:1', 'exists:vehicle_service_job_cards,id'],
            'labor_item_id' => [...$required, 'integer', 'min:1', 'exists:vehicle_service_labor_items,id'],
            'employee_id' => [...$required, 'integer', 'min:1', 'exists:employees,id'],
            'hours_worked' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'incentive_type' => ['nullable', 'string', 'max:40'],
            'incentive_value' => ['nullable', 'numeric', 'min:0'],
            'split_type' => ['nullable', 'string', 'max:40'],
            'split_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:80'],
            'assigned_by' => ['nullable', 'integer', 'min:1'],
            'completed_by' => ['nullable', 'integer', 'min:1'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
