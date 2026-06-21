<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListRentalRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'status' => ['nullable', 'string', 'max:40'],
            'agreement_kind' => ['nullable', 'string', 'max:40'],
            'financial_side' => ['nullable', 'string', 'max:20'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'requested_vehicle_id' => ['nullable', 'integer', 'min:1'],
            'requested_vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_allocation_id' => ['nullable', 'integer', 'min:1'],
            'source_allocation_id' => ['nullable', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'replacement_id' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['nullable', 'string', 'max:40'],
            'expense_type' => ['nullable', 'string', 'max:40'],
            'calculation_status' => ['nullable', 'string', 'max:40'],
            'document_status' => ['nullable', 'string', 'max:40'],
            'vehicle_source_type' => ['nullable', 'string', 'max:40'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
