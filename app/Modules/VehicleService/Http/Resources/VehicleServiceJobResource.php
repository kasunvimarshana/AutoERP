<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

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
            'invoice_links' => $this->whenLoaded('invoiceLinks', fn () => $this->invoiceLinks->map(fn ($link) => [
                'id' => (int) $link->getKey(),
                'invoice_id' => (int) $link->invoice_id,
                'invoice_number' => $link->invoice?->invoice_number,
                'invoice_total' => (string) $link->invoice_total,
                'balance_due' => $link->invoice?->balance === null ? null : (string) $link->invoice->balance->balance_due,
                'status' => $link->status,
            ])->values()->all(), []),
            'payment_links' => $this->whenLoaded('paymentLinks', fn () => $this->paymentLinks->map(fn ($link) => [
                'id' => (int) $link->getKey(),
                'payment_id' => (int) $link->payment_id,
                'payment_number' => $link->payment?->payment_number,
                'invoice_id' => $link->invoice_id,
                'allocated_amount' => (string) $link->allocated_amount,
                'status' => $link->status,
            ])->values()->all(), []),
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
        ];
    }

    private function employeeSummary(mixed $employee): ?array
    {
        return $employee === null ? null : [
            'id' => (int) $employee->getKey(),
            'code' => $employee->employee_number,
            'name' => $employee->display_name,
        ];
    }
}
