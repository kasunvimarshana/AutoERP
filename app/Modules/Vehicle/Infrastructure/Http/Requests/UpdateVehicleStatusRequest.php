<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'rental_status' => 'nullable|in:available,reserved,rented,blocked',
            'service_status' => 'nullable|in:none,in_maintenance,under_repair,awaiting_parts,quality_check,ready_for_pickup,returned_to_fleet',
            'next_maintenance_due_at' => 'nullable|date',
        ];
    }
}
