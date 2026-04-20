<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'parent_id' => 'nullable|integer|exists:warehouse_locations,id',
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100',
            'type' => 'nullable|in:zone,aisle,rack,shelf,bin,staging,dispatch',
            'is_active' => 'nullable|boolean',
            'is_pickable' => 'nullable|boolean',
            'is_receivable' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:64',
        ];
    }
}
