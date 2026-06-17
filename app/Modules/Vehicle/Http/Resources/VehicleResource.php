<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            ...(new VehicleSummaryResource($this->resource))->resolve($request),
            'manufacture_year' => $this->manufacture_year,
            'registration_date' => $this->registration_date?->toDateString(),
            'color' => $this->color,
            'fuel_type' => $this->enumValue($this->fuel_type),
            'transmission_type' => $this->enumValue($this->transmission_type),
            'fuel_level' => $this->fuel_level,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'approved_at' => $this->approved_at?->toISOString(),
            'documents' => $this->whenLoaded('documents', fn () => VehicleDocumentResource::collection($this->documents)->resolve($request)),
            'ownerships' => $this->whenLoaded('ownerships', fn () => VehicleOwnershipResource::collection($this->ownerships)->resolve($request)),
            'attributes' => $this->whenLoaded('attributes', fn () => VehicleAttributeResource::collection($this->attributes)->resolve($request)),
            'status_history' => $this->whenLoaded('statusHistories', fn () => VehicleStatusHistoryResource::collection($this->statusHistories)->resolve($request)),
        ];
    }
}
