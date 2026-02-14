<?php

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'sometimes|required|integer|exists:tenants,id',
            'fleet_vehicle_id' => 'sometimes|required|integer|exists:fleet_vehicles,id',
            'maintenance_type' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'service_date' => 'sometimes|required|date',
            'mileage_at_service' => 'nullable|integer|min:0',
            'cost' => 'sometimes|required|numeric|min:0',
            'performed_by' => 'sometimes|required|string|max:255',
            'next_service_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'fleet_vehicle_id.exists' => 'The selected fleet vehicle does not exist.',
            'status.in' => 'The status must be one of: scheduled, in_progress, completed, cancelled.',
        ];
    }
}
