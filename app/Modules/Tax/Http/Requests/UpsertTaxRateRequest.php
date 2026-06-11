<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertTaxRateRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'rate' => ['required', 'decimal:0,6', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
