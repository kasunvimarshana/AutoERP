<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalMode;

final class StoreRentalReservationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'reservation_number' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'requested_vehicle_id' => ['nullable', 'integer', 'min:1'],
            'requested_vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'rental_mode' => ['required', Rule::enum(RentalMode::class)],
            'billing_cycle' => ['required', Rule::enum(RentalBillingCycle::class)],
            'requested_start_at' => ['required', 'date'],
            'requested_end_at' => ['required', 'date', 'after:requested_start_at'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'estimated_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'estimated_deposit_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'source' => ['nullable', 'string', 'max:30'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
