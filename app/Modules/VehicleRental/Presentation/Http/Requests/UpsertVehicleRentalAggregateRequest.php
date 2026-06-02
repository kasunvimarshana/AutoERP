<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVehicleRentalAggregateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'tenant_id' => [$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'lessee_agreement_id' => ['nullable', 'integer', 'min:1'],
            'lessor_agreement_id' => ['nullable', 'integer', 'min:1'],
            'agreement_number' => ['nullable', 'string', 'max:120'],
            'agreement_role' => ['nullable', 'string', 'max:40'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'rental_vehicle_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'provider_id' => ['nullable', 'integer', 'min:1'],
            'lessor_party_type' => ['nullable', 'string', 'max:80'],
            'lessor_party_id' => ['nullable', 'integer', 'min:1'],
            'lessor_party_name' => ['nullable', 'string', 'max:255'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'assigned_driver_id' => ['nullable', 'integer', 'min:1'],
            'chart_date' => ['nullable', 'date'],
            'agreement_side' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:80'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'metadata' => ['nullable', 'array'],
            'lessee_agreement' => ['nullable', 'array'],
            'lessor_agreement' => ['nullable', 'array'],
            'lines' => ['nullable', 'array'],
            'lessee_lines' => ['nullable', 'array'],
            'lessor_lines' => ['nullable', 'array'],
            'rates' => ['nullable', 'array'],
            'lessee_rates' => ['nullable', 'array'],
            'lessor_rates' => ['nullable', 'array'],
            'rate_rules' => ['nullable', 'array'],
            'extra_charges' => ['nullable', 'array'],
        ];
    }
}
