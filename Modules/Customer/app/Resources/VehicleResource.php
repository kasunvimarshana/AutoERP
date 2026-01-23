<?php

declare(strict_types=1);

namespace Modules\Customer\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vehicle Resource
 *
 * Transforms Vehicle model data for API responses
 */
class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'vehicle_number' => $this->vehicle_number,
            'registration_number' => $this->registration_number,
            'vin' => $this->vin,
            'display_name' => $this->display_name,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'engine_number' => $this->engine_number,
            'chassis_number' => $this->chassis_number,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'mileage' => [
                'current' => $this->current_mileage,
                'next_service' => $this->next_service_mileage,
            ],
            'dates' => [
                'purchase' => $this->purchase_date?->format('Y-m-d'),
                'registration' => $this->registration_date?->format('Y-m-d'),
                'last_service' => $this->last_service_date?->format('Y-m-d H:i:s'),
                'next_service' => $this->next_service_date?->format('Y-m-d'),
            ],
            'insurance' => [
                'provider' => $this->insurance_provider,
                'policy_number' => $this->insurance_policy_number,
                'expiry' => $this->insurance_expiry?->format('Y-m-d'),
                'is_expiring_soon' => $this->isInsuranceExpiringSoon(),
            ],
            'status' => $this->status,
            'notes' => $this->notes,
            'service_due' => [
                'by_mileage' => $this->isDueForServiceByMileage(),
                'by_date' => $this->isDueForServiceByDate(),
            ],
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'service_records_count' => $this->whenCounted('serviceRecords'),
            'service_records' => VehicleServiceRecordResource::collection($this->whenLoaded('serviceRecords')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
