<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleDocumentResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'document_type' => $this->enumValue($this->document_type),
            'document_number' => $this->document_number,
            'issued_date' => $this->issued_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
