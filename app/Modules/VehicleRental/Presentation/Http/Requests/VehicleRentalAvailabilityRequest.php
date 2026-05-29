<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VehicleRentalAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'rental_vehicle_id' => ['required', 'integer', 'min:1'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'exclude_agreement_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
