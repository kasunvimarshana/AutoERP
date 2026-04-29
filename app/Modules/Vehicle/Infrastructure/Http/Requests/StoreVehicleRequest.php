<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Requests;

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
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'ownership_type' => 'required|in:company_owned,third_party_owned,customer_owned,leased',
            'asset_code' => 'nullable|string|max:120',
            'make' => 'required|string|max:120',
            'model' => 'required|string|max:120',
            'year' => 'nullable|integer|min:1900|max:2100',
            'vin' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('vehicles', 'vin')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'registration_number' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('vehicles', 'registration_number')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'chassis_number' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('vehicles', 'chassis_number')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'fuel_type' => 'nullable|in:petrol,diesel,hybrid,electric,cng,lpg,other',
            'transmission' => 'nullable|in:manual,automatic,cvt,semi_automatic,other',
            'odometer' => 'nullable|numeric|min:0',
            'rental_status' => 'nullable|in:available,reserved,rented,blocked',
            'service_status' => 'nullable|in:none,in_maintenance,under_repair,awaiting_parts,quality_check,ready_for_pickup,returned_to_fleet',
            'next_maintenance_due_at' => 'nullable|date',
            'primary_image_path' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'documents' => 'nullable|array',
            'documents.*.document_type' => 'required_with:documents|in:registration,insurance,fitness,permit,other',
            'documents.*.document_number' => 'nullable|string|max:120',
            'documents.*.issued_at' => 'nullable|date',
            'documents.*.expires_at' => 'nullable|date',
            'documents.*.file_path' => 'nullable|string|max:500',
            'documents.*.metadata' => 'nullable|array',
        ];
    }
}
