<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRentalIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_booking_id' => ['required', 'integer', 'min:1'],
            'asset_id' => ['required', 'integer', 'min:1'],
            'incident_type' => ['required', 'string', 'in:damage,traffic_violation,late_return,other'],
            'org_unit_id' => ['sometimes', 'integer', 'min:1'],
            'occurred_at' => ['sometimes', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'estimated_cost' => ['sometimes', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
