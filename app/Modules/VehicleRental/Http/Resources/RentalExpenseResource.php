<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalExpenseResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'expense_number' => $this->expense_number,
            'agreement' => $this->whenLoaded('agreement', fn () => $this->summary($this->agreement, ['agreement_number', 'agreement_kind'])),
            'allocation' => $this->whenLoaded('allocation', fn () => $this->summary($this->allocation, ['allocation_number', 'status'])),
            'usage_log' => $this->whenLoaded('usageLog', fn () => $this->summary($this->usageLog, ['usage_number', 'usage_date', 'status'])),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number'])),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'name', 'display_name'])),
            'employee' => $this->whenLoaded('employee', fn () => $this->summary($this->employee, ['employee_number', 'first_name', 'last_name', 'display_name'])),
            'expense_type' => $this->enumValue($this->expense_type),
            'expense_date' => $this->expense_date?->toDateString(),
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'symbol'])),
            'net_amount' => $this->decimal($this->net_amount),
            'tax_amount' => $this->decimal($this->tax_amount),
            'gross_amount' => $this->decimal($this->gross_amount),
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'status' => $this->enumValue($this->status),
            'allocations' => $this->loadedCollection('allocations', fn ($allocation): array => [
                'id' => (int) $allocation->getKey(), 'sequence' => (int) $allocation->sequence,
                'allocation_type' => $this->enumValue($allocation->allocation_type),
                'net_amount' => $this->decimal($allocation->net_amount), 'tax_amount' => $this->decimal($allocation->tax_amount),
                'withholding_amount' => $this->decimal($allocation->withholding_amount), 'markup_amount' => $this->decimal($allocation->markup_amount),
                'total_amount' => $this->decimal($allocation->total_amount), 'status' => $this->enumValue($allocation->status),
            ]),
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
