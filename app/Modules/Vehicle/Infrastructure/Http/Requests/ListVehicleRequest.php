<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'ownership_type' => 'nullable|in:company_owned,third_party_owned,customer_owned,leased',
            'rental_status' => 'nullable|in:available,reserved,rented,blocked',
            'service_status' => 'nullable|in:none,in_maintenance,under_repair,awaiting_parts,quality_check,ready_for_pickup,returned_to_fleet',
            'is_active' => 'nullable|boolean',
            'make' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:64',
        ];
    }
}
