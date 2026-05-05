<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'assigned_driver_id' => 'nullable|integer|exists:users,id',
            'rental_no' => 'required|string|max:120',
            'rental_status' => 'nullable|in:draft,reserved,active,completed,cancelled',
            'pricing_model' => 'required|in:hourly,daily,weekly,monthly,kilometer',
            'base_rate' => 'required|numeric|min:0',
            'units' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'included_km' => 'nullable|numeric|min:0',
            'extra_km_rate' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'reserved_from' => 'nullable|date',
            'reserved_until' => 'nullable|date|after_or_equal:reserved_from',
            'odometer_out' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
