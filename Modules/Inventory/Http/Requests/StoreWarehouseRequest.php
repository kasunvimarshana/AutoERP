<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Enums\WarehouseStatus;
use Modules\Inventory\Models\Warehouse;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Warehouse::class);
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(WarehouseStatus::class)],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'address_line1' => 'address line 1',
            'address_line2' => 'address line 2',
            'postal_code' => 'postal code',
            'manager_name' => 'manager name',
            'is_default' => 'default warehouse',
        ];
    }
}
