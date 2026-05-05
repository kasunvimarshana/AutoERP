<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'driver_id' => $this->resource->driverId,
            'license_number' => $this->resource->licenseNumber,
            'category' => $this->resource->category,
            'issued_date' => $this->resource->issuedDate->format('Y-m-d'),
            'expiry_date' => $this->resource->expiryDate->format('Y-m-d'),
            'issuing_country' => $this->resource->issuingCountry,
        ];
    }
}
