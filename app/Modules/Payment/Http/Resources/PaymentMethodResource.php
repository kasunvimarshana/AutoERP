<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class PaymentMethodResource extends ModuleResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'method_type' => $this->enumValue($this->method_type),
            'direction_allowed' => $this->enumValue($this->direction_allowed),
            'requires_reference' => (bool) $this->requires_reference,
            'requires_bank_account' => (bool) $this->requires_bank_account,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'metadata' => $this->metadata,
            'scope' => $this->organization_unit_id !== null ? 'organization' : ($this->tenant_id !== null ? 'tenant' : 'global'),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
