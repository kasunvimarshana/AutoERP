<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'sometimes|required|string|max:255',
            'type'           => 'sometimes|required|string|max:100',
            'code'           => 'nullable|string|max:50',
            'description'    => 'nullable|string',
            'capacity'       => 'nullable|numeric|min:0',
            'metadata'       => 'nullable|array',
            'is_active'      => 'boolean',
            'parent_zone_id' => 'nullable|integer|exists:warehouse_zones,id',
        ];
    }
}
