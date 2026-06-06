<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

abstract class ItemBrandRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        return [
            'code' => (string) $this->input('code'),
            'name' => (string) $this->input('name'),
            'description' => $this->filled('description') ? (string) $this->input('description') : null,
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
