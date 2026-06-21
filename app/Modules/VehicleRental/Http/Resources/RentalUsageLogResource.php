<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalUsageLogResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'usage_number' => $this->usage_number,
            'allocation' => $this->whenLoaded('allocation', fn () => $this->summary($this->allocation, ['allocation_number', 'status'])),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number'])),
            'driver' => $this->whenLoaded('driver', fn () => $this->summary($this->driver, ['employee_number', 'first_name', 'last_name', 'display_name'])),
            'usage_date' => $this->usage_date?->toDateString(),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'start_odometer' => $this->decimal($this->start_odometer),
            'end_odometer' => $this->decimal($this->end_odometer),
            'distance_km' => $this->decimal($this->distance_km),
            'chargeable_distance_km' => $this->decimal($this->chargeable_distance_km),
            'garage_distance_km' => $this->decimal($this->garage_distance_km),
            'internal_distance_km' => $this->decimal($this->internal_distance_km),
            'working_minutes' => (int) $this->working_minutes,
            'normal_overtime_minutes' => (int) $this->normal_overtime_minutes,
            'double_overtime_minutes' => (int) $this->double_overtime_minutes,
            'triple_overtime_minutes' => (int) $this->triple_overtime_minutes,
            'night_out_count' => $this->decimal($this->night_out_count),
            'trip_from' => $this->trip_from,
            'trip_to' => $this->trip_to,
            'trip_purpose' => $this->trip_purpose,
            'status' => $this->enumValue($this->status),
            'events' => $this->loadedCollection('events', fn ($event): array => [
                'id' => (int) $event->getKey(), 'sequence' => (int) $event->sequence,
                'event_type' => $this->enumValue($event->event_type), 'occurred_at' => $event->occurred_at?->toISOString(),
                'quantity' => $this->decimal($event->quantity), 'unit' => $event->unit,
                'reference_number' => $event->reference_number, 'remarks' => $event->remarks,
            ]),
            'contexts' => $this->loadedCollection('contexts', fn ($context): array => [
                'id' => (int) $context->getKey(), 'financial_side' => $this->enumValue($context->financial_side),
                'agreement' => $context->relationLoaded('agreement') ? $this->summary($context->agreement, ['agreement_number', 'agreement_kind']) : null,
                'rate_version_id' => (int) $context->rate_version_id,
                'customer' => $context->relationLoaded('customer') ? $this->summary($context->customer, ['customer_number', 'name', 'display_name']) : null,
                'supplier' => $context->relationLoaded('supplier') ? $this->summary($context->supplier, ['supplier_number', 'name', 'display_name']) : null,
            ]),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
