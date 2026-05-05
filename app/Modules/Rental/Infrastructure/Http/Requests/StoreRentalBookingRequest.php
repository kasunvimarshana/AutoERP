<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'org_unit_id' => ['nullable', 'integer'],
            'customer_id' => ['required', 'integer'],
            'booking_number' => ['required', 'string', 'max:50'],
            'booking_type' => ['required', 'string', 'in:standard,long_term,contract'],
            'fleet_source' => ['required', 'string', 'in:own_fleet,third_party,mixed'],
            'status' => ['nullable', 'string', 'in:draft,confirmed,active,completed,cancelled'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
