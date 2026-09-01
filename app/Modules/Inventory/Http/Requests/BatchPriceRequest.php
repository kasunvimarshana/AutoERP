<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\BatchPriceData;
use Modules\Item\Enums\ItemPriceType;

abstract class BatchPriceRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['required', 'integer', 'min:1'],
            'price_type' => ['required', Rule::in([ItemPriceType::Sales->value, ItemPriceType::Service->value])],
            'currency_id' => ['required', 'integer', 'min:1'],
            'uom_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function toPriceData(): BatchPriceData
    {
        return new BatchPriceData(
            batchId: (int) $this->input('batch_id'),
            priceType: ItemPriceType::from((string) $this->input('price_type')),
            amount: (string) $this->input('amount'),
            currencyId: (int) $this->input('currency_id'),
            uomId: (int) $this->input('uom_id'),
            organizationUnitId: $this->organizationUnitId(),
            effectiveFrom: (string) $this->input('effective_from'),
            effectiveTo: $this->filled('effective_to') ? (string) $this->input('effective_to') : null,
        );
    }
}
