<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Models\RentalExpense;

/** @mixin RentalExpense */
final class RentalExpenseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_version' => $this->row_version,
            'expense_number' => $this->expense_number,
            'customer_agreement' => $this->customerAgreement ? [
                'id' => $this->customerAgreement->id,
                'reference' => $this->customerAgreement->reference,
            ] : null,
            'vehicle_use' => $this->vehicleUse ? [
                'id' => $this->vehicleUse->id,
                'label' => $this->vehicleUse->vehicle_label_snapshot ?? 'Vehicle Use',
            ] : null,
            'running_chart' => $this->runningChart ? [
                'id' => $this->runningChart->id,
                'reference' => $this->runningChart->reference,
            ] : null,
            'vehicle' => [
                'id' => $this->vehicle_id,
                'label' => $this->vehicle_label_snapshot,
                'make' => $this->vehicle?->make?->name,
                'model' => $this->vehicle?->model?->name,
            ],
            'supplier' => $this->supplier ? [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ] : null,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name ?? 'Employee',
            ] : null,
            'expense_type' => $this->expense_type->value,
            'expense_type_label' => $this->expense_type->label(),
            'expense_date' => $this->expense_date->toDateString(),
            'currency' => [
                'id' => $this->currency_id,
                'code' => $this->currency_code_snapshot,
            ],
            'net_amount' => (string) $this->net_amount,
            'tax_amount' => (string) $this->tax_amount,
            'gross_amount' => (string) $this->gross_amount,
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'allocations' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($a) => [
                'id' => $a->id,
                'sequence' => $a->sequence,
                'allocation_type' => $a->allocation_type->value,
                'allocation_type_label' => $a->allocation_type->label(),
                'net_amount' => (string) $a->net_amount,
                'tax_amount' => (string) $a->tax_amount,
                'total_amount' => (string) $a->total_amount,
                'customer' => $a->customer ? ['id' => $a->customer->id, 'name' => $a->customer->name] : null,
                'supplier' => $a->supplier ? ['id' => $a->supplier->id, 'name' => $a->supplier->name] : null,
                'target_customer_agreement' => $a->targetCustomerAgreement ? [
                    'id' => $a->targetCustomerAgreement->id,
                    'reference' => $a->targetCustomerAgreement->reference,
                ] : null,
                'target_owner_agreement' => $a->targetOwnerAgreement ? [
                    'id' => $a->targetOwnerAgreement->id,
                    'reference' => $a->targetOwnerAgreement->reference,
                ] : null,
            ])),
        ];
    }
}
