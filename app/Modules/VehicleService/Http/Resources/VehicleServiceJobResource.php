<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'job_number' => $this->job_number,
            'job_date' => $this->job_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'customer_id' => (int) $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customerRelation($this->customer)),
            'bill_to_customer_id' => $this->bill_to_customer_id === null ? null : (int) $this->bill_to_customer_id,
            'bill_to_customer' => $this->whenLoaded('billToCustomer', fn () => $this->customerRelation($this->billToCustomer)),
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

    private function vehicleSummary(): ?array
    {
        return $this->vehicle === null ? null : [
            'id' => (int) $this->vehicle->getKey(),
            'code' => $this->vehicle->vehicle_number,
            'name' => $this->vehicle->registration_number ?? $this->vehicle->code ?? $this->vehicle->vehicle_number,
            'registration_number' => $this->vehicle->registration_number,
            'make' => $this->vehicle->relationLoaded('make') ? $this->namedRelation($this->vehicle->make) : null,
            'model' => $this->vehicle->relationLoaded('model') ? $this->namedRelation($this->vehicle->model) : null,
            'current_ownerships' => $this->vehicleCurrentOwnerships(),
            'odometer_reading' => (string) $this->vehicle->odometer_reading,
            'odometer_unit' => $this->vehicle->odometer_unit,
        ];
    }

    private function vehicleCurrentOwnerships(): array
    {
        if (! $this->vehicle->relationLoaded('currentOwnerships')) {
            return [];
        }

        return $this->vehicle->currentOwnerships->map(static function ($ownership): array {
            $ownerType = $ownership->owner_type instanceof \BackedEnum
                ? $ownership->owner_type->value
                : (string) $ownership->owner_type;

            return [
                'id' => (int) $ownership->getKey(),
                'row_version' => (int) $ownership->row_version,
                'owner_type' => $ownerType,
                'owner_id' => $ownership->owner_id === null ? null : (int) $ownership->owner_id,
                'owner' => [
                    'id' => $ownership->owner_id === null ? null : (int) $ownership->owner_id,
                    'code' => $ownership->owner_code_snapshot,
                    'name' => $ownership->owner_name_snapshot,
                ],
                'ownership_type' => $ownership->ownership_type instanceof \BackedEnum
                    ? $ownership->ownership_type->value
                    : (string) $ownership->ownership_type,
                'started_at' => $ownership->started_at?->toISOString(),
                'ended_at' => $ownership->ended_at?->toISOString(),
                'is_current' => (bool) $ownership->is_current,
            ];
        })->values()->all();
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
}
