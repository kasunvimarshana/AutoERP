<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'method_type' => $this->enumValue($this->method_type),
            'direction_allowed' => $this->enumValue($this->direction_allowed),
            'requires_reference' => (bool) $this->requires_reference,
            'requires_instrument_details' => (bool) $this->requires_instrument_details,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'metadata' => $this->metadata,
            'scope' => $this->organization_unit_id !== null ? 'organization' : 'tenant',
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
