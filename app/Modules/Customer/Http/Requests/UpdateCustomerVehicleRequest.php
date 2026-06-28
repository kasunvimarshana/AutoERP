<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpdateCustomerVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['tenant_id' => ['required', 'integer'], 'organization_unit_id' => ['nullable', 'integer'], 'customer_id' => ['prohibited'], 'vehicle_id' => ['prohibited'], 'is_current' => ['prohibited'], 'relationship_type' => ['sometimes', 'nullable', Rule::in(['customer_owned'])], 'started_at' => ['sometimes', 'date'], 'ended_at' => ['sometimes', 'nullable', 'date'], 'notes' => ['sometimes', 'nullable', 'string', 'max:5000']];
    }
}
