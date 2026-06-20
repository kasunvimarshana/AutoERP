<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListSupplierVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['tenant_id' => ['required', 'integer'], 'organization_unit_id' => ['nullable', 'integer'], 'search' => ['nullable', 'string', 'max:150'], 'supplier_id' => ['nullable', 'integer'], 'vehicle_id' => ['nullable', 'integer'], 'is_current' => ['nullable', 'boolean'], 'status' => ['nullable', Rule::in(['active', 'ended'])], 'sort' => ['nullable', Rule::in(['started_at', 'ended_at', 'created_at'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']];
    }
}
