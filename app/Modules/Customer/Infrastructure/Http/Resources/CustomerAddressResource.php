<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'customer_id' => $this->getCustomerId(),
            'type' => $this->getType(),
            'label' => $this->getLabel(),
            'address_line1' => $this->getAddressLine1(),
            'address_line2' => $this->getAddressLine2(),
            'city' => $this->getCity(),
            'state' => $this->getState(),
            'postal_code' => $this->getPostalCode(),
            'country_id' => $this->getCountryId(),
            'is_default' => $this->isDefault(),
            'geo_lat' => $this->getGeoLat(),
            'geo_lng' => $this->getGeoLng(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
