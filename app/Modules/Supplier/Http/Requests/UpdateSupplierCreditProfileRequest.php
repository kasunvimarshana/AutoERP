<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierCreditProfileData;

final class UpdateSupplierCreditProfileRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'credit_limit' => ['nullable', 'decimal:0,6', 'gte:0'],
            'credit_period_days' => ['nullable', 'integer', 'min:0'],
            'warning_threshold_percent' => ['required', 'decimal:0,6', 'between:0,100'],
            'is_credit_allowed' => ['nullable', 'boolean'],
            'is_advance_allowed' => ['nullable', 'boolean'],
            'allow_over_credit' => ['nullable', 'boolean'],
            'allow_partial_payment' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): SupplierCreditProfileData
    {
        return new SupplierCreditProfileData(
            creditLimit: $this->has('credit_limit') && $this->input('credit_limit') !== null && $this->input('credit_limit') !== ''
                ? (string) $this->input('credit_limit')
                : null,
            creditPeriodDays: $this->filled('credit_period_days') ? (int) $this->input('credit_period_days') : null,
            warningThresholdPercent: (string) $this->input('warning_threshold_percent'),
            isCreditAllowed: $this->has('is_credit_allowed') ? $this->boolean('is_credit_allowed') : null,
            isAdvanceAllowed: $this->has('is_advance_allowed') ? $this->boolean('is_advance_allowed') : null,
            allowOverCredit: $this->boolean('allow_over_credit'),
            allowPartialPayment: $this->boolean('allow_partial_payment', true),
            isActive: $this->boolean('is_active', true),
        );
    }
}
