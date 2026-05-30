<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVehicleRequest extends FormRequest
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
            'metadata' => ['nullable', 'array'],
            'vehicle_code' => ['nullable', 'string', 'max:255'],
            'vin' => ['nullable', 'string', 'max:255'],
            'license_plate' => ['nullable', 'string', 'max:255'],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'digits:4'],
            'color' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'usage_profile' => ['nullable', 'string', 'in:rent_only,service_only,dual,internal'],
            'service_enabled' => ['nullable', 'boolean'],
            'rental_enabled' => ['nullable', 'boolean'],
            'fuel_type' => ['nullable', 'string', 'max:255'],
            'transmission' => ['nullable', 'string', 'max:255'],
            'seating_capacity' => ['nullable', 'integer', 'min:0', 'max:255'],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:draft,active,inactive,in_service,in_rental,under_maintenance,unavailable,sold,archived'],
            'registration_expiry' => ['nullable', 'date'],
            'insurance_expiry' => ['nullable', 'date'],
            'last_service_date' => ['nullable', 'date'],
            'last_service_odometer' => ['nullable', 'integer', 'min:0'],
            'next_service_due_date' => ['nullable', 'date'],
            'next_service_due_odometer' => ['nullable', 'integer', 'min:0'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
