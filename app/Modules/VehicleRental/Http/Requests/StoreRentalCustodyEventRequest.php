<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalCustodyItemType;

final class StoreRentalCustodyEventRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_allocation_version' => ['required', 'integer', 'min:1'],
            'event_type' => ['required', Rule::enum(RentalCustodyEventType::class)],
            'replacement_id' => ['nullable', 'integer', 'min:1'],
            'occurred_at' => ['required', 'date'],
            'odometer' => ['required', 'decimal:0,6', 'gte:0'],
            'fuel_level_percent' => ['nullable', 'decimal:0,4', 'between:0,100'],
            'location' => ['nullable', 'string', 'max:255'],
            'from_role' => ['nullable', Rule::in(['owner', 'company', 'customer'])],
            'to_role' => ['nullable', Rule::in(['owner', 'company', 'customer'])],
            'handed_over_by_employee_id' => ['nullable', 'integer', 'min:1'],
            'received_by_employee_id' => ['nullable', 'integer', 'min:1'],
            'external_handed_over_name' => ['nullable', 'string', 'max:150'],
            'external_received_by_name' => ['nullable', 'string', 'max:150'],
            'condition_summary' => ['nullable', 'string'],
            'damage_summary' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required', Rule::enum(RentalCustodyItemType::class)],
            'items.*.item_code' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string'],
            'items.*.expected_quantity' => ['nullable', 'decimal:0,6', 'gte:0'],
            'items.*.actual_quantity' => ['nullable', 'decimal:0,6', 'gte:0'],
            'items.*.condition_status' => ['nullable', Rule::in(['good', 'damaged', 'missing', 'not_applicable'])],
            'items.*.is_chargeable' => ['nullable', 'boolean'],
            'items.*.estimated_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'items.*.responsible_side' => ['nullable', Rule::in(['customer', 'owner', 'company'])],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }
}
