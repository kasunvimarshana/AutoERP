<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'vehicle_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vehicles', 'vehicle_code')->where('tenant_id', $tenantId),
            ],
            'vin' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vehicles', 'vin')->where('tenant_id', $tenantId),
            ],
            'license_plate' => ['nullable', 'string', 'max:255'],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'digits:4', 'min:1900'],
            'color' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'usage_profile' => ['nullable', 'string', 'in:rent_only,service_only,dual,internal'],
            'fuel_type' => ['nullable', 'string', 'max:255'],
            'transmission' => ['nullable', 'string', 'max:255'],
            'seating_capacity' => ['nullable', 'integer', 'min:0', 'max:255'],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'registration_expiry' => ['nullable', 'date'],
            'insurance_expiry' => ['nullable', 'date'],
            'last_service_date' => ['nullable', 'date'],
            'last_service_odometer' => ['nullable', 'integer', 'min:0'],
            'next_service_due_date' => ['nullable', 'date'],
            'next_service_due_odometer' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
