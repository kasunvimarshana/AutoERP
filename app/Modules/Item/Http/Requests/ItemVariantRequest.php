<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\ItemVariantData;

abstract class ItemVariantRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'attributes' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ItemVariantData
    {
        return new ItemVariantData(
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            sku: $this->filled('sku') ? (string) $this->input('sku') : null,
            barcode: $this->filled('barcode') ? (string) $this->input('barcode') : null,
            attributes: $this->input('attributes'),
            isActive: $this->boolean('is_active', true),
        );
    }
}
