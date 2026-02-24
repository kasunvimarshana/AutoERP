<?php

namespace Modules\Fleet\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'maintenance_type' => ['required', 'in:oil_change,tire_rotation,inspection,repair,other'],
            'performed_at'     => ['required', 'date'],
            'cost'             => ['nullable', 'numeric', 'min:0'],
            'odometer_km'      => ['nullable', 'integer', 'min:0'],
            'performed_by'     => ['nullable', 'uuid'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
