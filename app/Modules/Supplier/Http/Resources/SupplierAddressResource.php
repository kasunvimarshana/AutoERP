<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Http\Resources\Concerns\FormatsSupplierResources;

final class SupplierAddressResource extends JsonResource
{
    use FormatsSupplierResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'address_type' => $this->enumValue($this->address_type),
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
