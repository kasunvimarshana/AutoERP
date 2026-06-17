<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Vehicle\Models\VehicleOwnership;

final class VehicleServiceJobResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'job_number' => $this->job_number,
            'job_date' => $this->job_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'customer_id' => (int) $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customerSummary()),
            'vehicle_id' => (int) $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicleSummary()),
            'supervisor_employee_id' => $this->supervisor_employee_id,
            'supervisor' => $this->whenLoaded('supervisor', fn () => $this->employeeSummary($this->supervisor)),
            'supervisor_commission_type' => $this->enum($this->supervisor_commission_type),
            'supervisor_commission_value' => (string) $this->supervisor_commission_value,
            'supervisor_commission_amount' => (string) $this->supervisor_commission_amount,
            'status' => $this->enum($this->status),
            'status_label' => str((string) $this->enum($this->status))->replace('_', ' ')->title()->toString(),
            'odometer_reading' => $this->odometer_reading === null ? null : (string) $this->odometer_reading,
            'fuel_level' => $this->fuel_level,
            'priority' => $this->priority,
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'charge_total' => (string) $this->charge_total,
            'grand_total' => (string) $this->grand_total,
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at?->toISOString(),
            'inspection' => $this->whenLoaded('inspection', fn () => $this->inspection === null ? null : (new VehicleServiceInspectionResource($this->inspection))->resolve($request)),
            'lines' => $this->whenLoaded('lines', fn () => VehicleServiceJobLineResource::collection($this->lines)->resolve($request), []),
            'invoice_links' => $this->whenLoaded(
                'invoiceLinks',
                fn () => VehicleServiceInvoiceLinkResource::collection($this->invoiceLinks)->resolve($request),
                [],
            ),
            'payment_links' => $this->whenLoaded(
                'paymentLinks',
                fn () => VehicleServicePaymentLinkResource::collection($this->paymentLinks)->resolve($request),
                [],
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function customerSummary(): ?array
    {
        return $this->customer === null ? null : [
            'id' => (int) $this->customer->getKey(),
            'code' => $this->customer->code,
            'name' => $this->customer->display_name ?? $this->customer->name,
        ];
    }

    private function vehicleSummary(): ?array
    {
        return $this->vehicle === null ? null : [
            'id' => (int) $this->vehicle->getKey(),
            'code' => $this->vehicle->vehicle_number,
            'name' => $this->vehicle->registration_number ?? $this->vehicle->code ?? $this->vehicle->vehicle_number,
            'registration_number' => $this->vehicle->registration_number,
            'make' => $this->vehicle->relationLoaded('make') ? $this->namedRelation($this->vehicle->make) : null,
            'model' => $this->vehicle->relationLoaded('model') ? $this->namedRelation($this->vehicle->model) : null,
            'current_ownerships' => $this->vehicle->relationLoaded('currentOwnerships') ? $this->vehicleCurrentOwnerships() : [],
            'odometer_reading' => (string) $this->vehicle->odometer_reading,
            'odometer_unit' => $this->vehicle->odometer_unit,
        ];
    }

    private function vehicleCurrentOwnerships(): array
    {
        return $this->vehicle->currentOwnerships
            ->map(fn ($ownership): array => [
                'id' => (int) $ownership->getKey(),
                'owner_type' => $ownership->owner_type,
                'owner_id' => $ownership->owner_id,
                'owner' => $this->ownershipOwner($ownership),
                'ownership_type' => $this->enum($ownership->ownership_type),
                'started_at' => $ownership->started_at?->toISOString(),
                'ended_at' => $ownership->ended_at?->toISOString(),
                'is_current' => (bool) $ownership->is_current,
            ])
            ->all();
    }

    private function employeeSummary(mixed $employee): ?array
    {
        return $employee === null ? null : [
            'id' => (int) $employee->getKey(),
            'code' => $employee->employee_number,
            'name' => $employee->display_name,
        ];
    }

    private function namedRelation(mixed $model): ?array
    {
        return $model === null ? null : [
            'id' => (int) $model->getKey(),
            'code' => $model->code,
            'name' => $model->name,
        ];
    }

    private function customerRelation(mixed $customer): ?array
    {
        return $customer === null ? null : [
            'id' => (int) $customer->getKey(),
            'code' => $customer->code,
            'name' => $customer->display_name ?? $customer->name,
        ];
    }

    private function ownershipOwner(mixed $ownership): ?array
    {
        return match ($ownership->owner_type) {
            VehicleOwnership::OWNER_TYPE_CUSTOMER => $ownership->relationLoaded('customerOwner') ? $this->customerRelation($ownership->customerOwner) : null,
            VehicleOwnership::OWNER_TYPE_SUPPLIER, VehicleOwnership::OWNER_TYPE_OWNER => $ownership->relationLoaded('supplierOwner') ? $this->namedRelation($ownership->supplierOwner) : null,
            default => null,
        };
    }
}
