<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;

final class UpsertPaymentMethodRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'method_type' => ['required', Rule::enum(PaymentMethodType::class)],
            'direction_allowed' => ['nullable', Rule::enum(PaymentMethodDirection::class)],
            'requires_reference' => ['nullable', 'boolean'],
            'requires_instrument_details' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
