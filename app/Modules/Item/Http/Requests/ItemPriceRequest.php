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
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ItemPriceData
    {
        return new ItemPriceData(
            priceType: ItemPriceType::from((string) $this->input('price_type')),
            amount: (string) $this->input('amount'),
            itemVariantId: $this->filled('item_variant_id') ? (int) $this->input('item_variant_id') : null,
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            uomId: $this->filled('uom_id') ? (int) $this->input('uom_id') : null,
            effectiveFrom: $this->filled('effective_from') ? (string) $this->input('effective_from') : null,
            effectiveTo: $this->filled('effective_to') ? (string) $this->input('effective_to') : null,
            isActive: $this->boolean('is_active', true),
        );
    }
}
