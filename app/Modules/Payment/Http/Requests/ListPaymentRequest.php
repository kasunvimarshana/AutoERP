<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListPaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'payment_type' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
