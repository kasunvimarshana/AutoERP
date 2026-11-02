<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Models\RentalReservation;

/** @mixin RentalReservation */
final class RentalReservationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_version' => $this->row_version,
            'reservation_number' => $this->reservation_number,
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer_name_snapshot,
            ],
            'requested_vehicle' => $this->requestedVehicle ? [
                'id' => $this->requestedVehicle->id,
                'vehicle_number' => $this->requestedVehicle->vehicle_number,
                'registration_number' => $this->requestedVehicle->registration_number,
                'label' => $this->requestedVehicle->registration_number ?? $this->requestedVehicle->vehicle_number,
                'make' => $this->requestedVehicle->make?->name,
                'model' => $this->requestedVehicle->model?->name,
            ] : null,
            'requested_vehicle_category' => $this->requestedVehicleCategory ? [
                'id' => $this->requestedVehicleCategory->id,
                'name' => $this->requestedVehicleCategory->name,
            ] : null,
            'rental_mode' => $this->rental_mode->value,
            'billing_cycle' => $this->billing_cycle->value,
            'requested_start_at' => $this->requested_start_at->toIso8601String(),
            'requested_end_at' => $this->requested_end_at->toIso8601String(),
            'currency' => [
                'id' => $this->currency_id,
                'code' => $this->currency_code_snapshot,
            ],
            'estimated_amount' => (string) $this->estimated_amount,
            'estimated_deposit_amount' => (string) $this->estimated_deposit_amount,
            'status' => $this->status->value,
            'source' => $this->source,
            'remarks' => $this->remarks,
            'converted_agreement' => $this->convertedAgreement ? [
                'id' => $this->convertedAgreement->id,
                'reference' => $this->convertedAgreement->reference,
            ] : null,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'history' => $this->whenLoaded('history', fn () => $this->history->map(fn ($h) => [
                'id' => $h->id,
                'from_status' => $h->from_status,
                'to_status' => $h->to_status,
                'reason' => $h->reason,
                'changed_by' => $h->user?->name ?? 'System',
                'changed_at' => $h->changed_at->toIso8601String(),
            ])),
        ];
    }
}
