<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class EndSupplierVehicleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['tenant_id' => ['required', 'integer'], 'organization_unit_id' => ['nullable', 'integer'], 'ended_at' => ['nullable', 'date']];
    }
}
