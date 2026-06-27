<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class FinanceActionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'reversal_date' => ['nullable', 'date'],
            'reversal_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }
}
