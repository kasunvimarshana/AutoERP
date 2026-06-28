<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class StoreCustomerVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['tenant_id' => ['required', 'integer'], 'organization_unit_id' => ['nullable', 'integer'], 'customer_id' => ['required', 'integer', 'min:1'], 'vehicle_id' => ['required', 'integer', 'min:1'], 'relationship_type' => ['nullable', Rule::in(['customer_owned'])], 'started_at' => ['required', 'date'], 'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'], 'is_current' => ['nullable', 'boolean'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
