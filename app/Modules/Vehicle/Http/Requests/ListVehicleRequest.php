<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\Enums\VehicleStatus;

final class ListVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
            'vehicle_make_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_model_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_type_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'ownership_scope' => ['nullable', Rule::in(['customer', 'supplier'])],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['vehicle_number', 'code', 'registration_number', 'status', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
