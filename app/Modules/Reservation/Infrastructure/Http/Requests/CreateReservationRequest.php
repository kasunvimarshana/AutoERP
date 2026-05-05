<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_unit_id' => ['nullable', 'uuid'],
            'reservation_number' => ['required', 'string', 'max:64'],
            'vehicle_id' => ['required', 'uuid'],
            'customer_id' => ['required', 'uuid'],
            'reserved_from' => ['required', 'date'],
            'reserved_to' => ['required', 'date', 'after:reserved_from'],
            'estimated_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
