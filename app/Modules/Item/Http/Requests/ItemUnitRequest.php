<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\Enums\ItemUnitRole;

abstract class ItemUnitRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['required', 'integer', 'min:1'],
            'unit_role' => ['required', Rule::enum(ItemUnitRole::class)],
            'conversion_factor' => ['required', 'decimal:0,6', 'gt:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ItemUnitData
    {
        return new ItemUnitData(
            uomId: (int) $this->input('uom_id'),
            unitRole: ItemUnitRole::from((string) $this->input('unit_role')),
            conversionFactor: (string) $this->input('conversion_factor'),
            isDefault: $this->boolean('is_default'),
            isActive: $this->boolean('is_active', true),
        );
    }
}
