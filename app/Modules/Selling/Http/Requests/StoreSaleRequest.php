<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Selling\Enums\SaleDiscountType;

final class StoreSaleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'sale_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:sale_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'bill_discount_type' => ['nullable', Rule::enum(SaleDiscountType::class)],
            'bill_discount_value' => ['nullable', 'decimal:0,6', 'min:0', 'required_with:bill_discount_type'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['required', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_number_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.discount_type' => ['nullable', Rule::enum(SaleDiscountType::class)],
            'lines.*.discount_value' => ['nullable', 'decimal:0,6', 'min:0', 'required_with:lines.*.discount_type'],
            'idempotency_key' => ['prohibited'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return array_merge($this->validated(), [
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'current_user_id' => $this->currentUserId(),
        ]);
    }
}
