<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\ItemCodeData;
use Modules\Item\Enums\ItemCodeType;

abstract class ItemCodeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code_type' => ['required', Rule::enum(ItemCodeType::class)],
            'code' => ['required', 'string', 'max:120'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'party_type' => ['nullable', 'string', 'max:255'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ItemCodeData
    {
        return new ItemCodeData(
            codeType: ItemCodeType::from((string) $this->input('code_type')),
            code: (string) $this->input('code'),
            itemVariantId: $this->filled('item_variant_id') ? (int) $this->input('item_variant_id') : null,
            partyType: $this->filled('party_type') ? (string) $this->input('party_type') : null,
            partyId: $this->filled('party_id') ? (int) $this->input('party_id') : null,
            isPrimary: $this->boolean('is_primary'),
        );
    }
}
