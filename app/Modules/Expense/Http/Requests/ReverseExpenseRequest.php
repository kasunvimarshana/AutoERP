<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ReverseExpenseRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
