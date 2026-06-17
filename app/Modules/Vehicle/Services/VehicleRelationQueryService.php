<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleAttribute;
use Modules\Vehicle\Models\VehicleDocument;
use Modules\Vehicle\Models\VehicleOwnership;

final class VehicleRelationQueryService
{
    public function documents(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->documents()->orderBy('document_type')->orderByDesc('expiry_date')->paginate($perPage);
    }

    public function ownerships(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->ownerships()->with(['customerOwner', 'supplierOwner'])->orderByDesc('is_current')->orderByDesc('started_at')->paginate($perPage);
    }

    public function attributes(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->attributes()->orderBy('sort_order')->orderBy('attribute_key')->paginate($perPage);
    }

    public function statusHistory(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->statusHistories()->orderByDesc('changed_at')->orderByDesc('id')->paginate($perPage);
    }

    public function document(Vehicle $vehicle, int $id): VehicleDocument { return $this->relation($vehicle, VehicleDocument::class, $id); }
    public function ownership(Vehicle $vehicle, int $id): VehicleOwnership { return $this->relation($vehicle, VehicleOwnership::class, $id); }
    public function attribute(Vehicle $vehicle, int $id): VehicleAttribute { return $this->relation($vehicle, VehicleAttribute::class, $id); }

    private function relation(Vehicle $vehicle, string $model, int $id): Model
    {
        return $model::query()->where('vehicle_id', $vehicle->getKey())->findOrFail($id);
    }
}
