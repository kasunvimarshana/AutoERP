<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertTaxPostingProfileRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'tax_id' => ['required', 'integer', 'min:1', $this->tenantExists('taxes', 'id')],
            'direction' => ['required', 'string', 'max:50'],
            'account_id' => ['required', 'integer', 'min:1', $this->tenantExists('finance_accounts', 'id')],
            'posting_key' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
