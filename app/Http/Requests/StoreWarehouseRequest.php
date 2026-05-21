<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'nullable|integer|exists:organization_units,id',
            'name' => 'required|string',
            'code' => 'required|string',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'path' => 'required|string',
            'type' => 'required|string',
            'is_active' => 'required|boolean',
            'is_default' => 'required|boolean',

            'locations' => 'required|array',
            'locations.*.id' => 'nullable|integer|exists:warehouse_locations,id',
            'locations.*.warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'locations.*.parent_id' => 'nullable|integer|exists:warehouse_locations,id',
            'locations.*.name' => 'required|string',
            'locations.*.code' => 'required|string',
            'locations.*.path' => 'required|string',
            'locations.*.depth' => 'required|integer',
            'locations.*.type' => 'required|string',
            'locations.*.is_active' => 'required|boolean',
            'locations.*.is_pickable' => 'required|boolean',
            'locations.*.is_receivable' => 'required|boolean',
            'locations.*.capacity' => 'required|integer',
        ];
    }
}
