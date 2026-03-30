<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:100',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'address'     => 'nullable|string|max:500',
            'capacity'    => 'nullable|numeric|min:0',
            'location_id' => 'nullable|integer|exists:locations,id',
            'metadata'    => 'nullable|array',
            'is_active'   => 'boolean',
        ];
    }
}
