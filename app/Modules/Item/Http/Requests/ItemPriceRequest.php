<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\Enums\ItemPriceType;

abstract class ItemPriceRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'price_type' => ['required', Rule::enum(ItemPriceType::class)],
            'amount' => ['required', 'decimal:0,6', 'gte:0'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'uom_id' => ['required', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function toPriceData(): ItemPriceData
    {
        return new ItemPriceData(
            priceType: ItemPriceType::from((string) $this->input('price_type')),
            amount: (string) $this->input('amount'),
            currencyId: (int) $this->input('currency_id'),
            uomId: (int) $this->input('uom_id'),
            organizationUnitId: $this->organizationUnitId(),
            effectiveFrom: (string) $this->input('effective_from'),
            itemVariantId: $this->filled('item_variant_id') ? (int) $this->input('item_variant_id') : null,
            effectiveTo: $this->filled('effective_to') ? (string) $this->input('effective_to') : null,
        );
    }
}
