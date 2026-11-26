<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\DTOs\CustomerCreditProfileData;

final class UpdateCustomerCreditProfileRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'credit_limit' => ['nullable', 'decimal:0,6', 'gte:0'],
            'credit_period_days' => ['nullable', 'integer', 'min:0'],
            'warning_threshold_percent' => ['required', 'decimal:0,6', 'between:0,100'],
            'credit_allowed' => ['required', 'boolean'],
            'advance_allowed' => ['required', 'boolean'],
            'allow_over_credit' => ['nullable', 'boolean'],
            'allow_partial_payment' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): CustomerCreditProfileData
    {
        return new CustomerCreditProfileData(
            creditLimit: $this->has('credit_limit') && $this->input('credit_limit') !== null && $this->input('credit_limit') !== ''
                ? (string) $this->input('credit_limit')
                : null,
            creditPeriodDays: $this->filled('credit_period_days') ? (int) $this->input('credit_period_days') : null,
            warningThresholdPercent: (string) $this->input('warning_threshold_percent'),
            creditAllowed: $this->boolean('credit_allowed'),
            advanceAllowed: $this->boolean('advance_allowed'),
            allowOverCredit: $this->boolean('allow_over_credit'),
            allowPartialPayment: $this->boolean('allow_partial_payment', true),
            isActive: $this->boolean('is_active', true),
            rowVersion: $this->filled('row_version') ? (int) $this->input('row_version') : null,
        );
    }
}
