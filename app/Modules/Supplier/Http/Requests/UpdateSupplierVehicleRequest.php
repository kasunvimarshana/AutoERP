<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpdateSupplierVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['tenant_id' => ['required', 'integer'], 'organization_unit_id' => ['nullable', 'integer'], 'supplier_id' => ['prohibited'], 'vehicle_id' => ['prohibited'], 'is_current' => ['prohibited'], 'relationship_type' => ['sometimes', 'nullable', Rule::in(['leased', 'rented', 'third_party'])], 'started_at' => ['sometimes', 'date'], 'ended_at' => ['sometimes', 'nullable', 'date'], 'notes' => ['sometimes', 'nullable', 'string', 'max:5000']];
    }
}
