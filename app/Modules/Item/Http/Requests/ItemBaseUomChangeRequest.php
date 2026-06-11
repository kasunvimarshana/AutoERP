<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ItemBaseUomChangeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'new_base_uom_id' => ['required', 'integer', 'min:1'],
            'conversion_factor' => ['nullable', 'string', 'regex:/^\d+(\.\d{1,6})?$/'],
            'effective_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function newBaseUomId(): int
    {
        return (int) $this->validated('new_base_uom_id');
    }

    public function conversionFactor(): ?string
    {
        $value = $this->validated('conversion_factor');

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    public function effectiveAt(): ?string
    {
        $value = $this->validated('effective_at');

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    public function reason(): ?string
    {
        $value = $this->validated('reason');

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
