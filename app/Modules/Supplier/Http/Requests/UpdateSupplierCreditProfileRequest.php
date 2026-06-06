<?php

declare(strict_types=1);

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
            'credit_limit' => ['required', 'decimal:0,6', 'gte:0'],
            'credit_period_days' => ['nullable', 'integer', 'min:0'],
            'warning_threshold_percent' => ['required', 'decimal:0,6', 'between:0,100'],
            'allow_over_credit' => ['nullable', 'boolean'],
            'allow_partial_payment' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): SupplierCreditProfileData
    {
        return new SupplierCreditProfileData(
            creditLimit: (string) $this->input('credit_limit'),
            creditPeriodDays: $this->filled('credit_period_days') ? (int) $this->input('credit_period_days') : null,
            warningThresholdPercent: (string) $this->input('warning_threshold_percent'),
            allowOverCredit: $this->boolean('allow_over_credit'),
            allowPartialPayment: $this->boolean('allow_partial_payment', true),
            isActive: $this->boolean('is_active', true),
        );
    }
}
