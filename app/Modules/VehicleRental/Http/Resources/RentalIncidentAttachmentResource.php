<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalIncidentAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => (int) $this->size_bytes,
            'checksum_sha256' => $this->checksum_sha256,
            'recorded_at' => $this->recorded_at?->toISOString(),
        ];
    }
}
