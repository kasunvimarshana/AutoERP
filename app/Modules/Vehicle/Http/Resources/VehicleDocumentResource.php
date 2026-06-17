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
            'file_name' => $this->file_path ? basename((string) $this->file_path) : null,
            'has_file' => is_string($this->file_path) && $this->file_path !== '',
            'preview_url' => $this->file_path ? sprintf('/api/v1/vehicles/%d/documents/%d/preview', (int) $this->vehicle_id, (int) $this->getKey()) : null,
            'download_url' => $this->file_path ? sprintf('/api/v1/vehicles/%d/documents/%d/download', (int) $this->vehicle_id, (int) $this->getKey()) : null,
            'status' => $this->enumValue($this->status),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
