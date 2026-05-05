<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'         => ['required', 'integer', 'min:1'],
            'vehicle_id'          => ['required', 'integer', 'min:1'],
            'driver_id'           => ['nullable', 'integer', 'min:1'],
            'rental_number'       => ['required', 'string', 'max:50'],
            'rental_type'         => ['required', 'string', 'in:self_drive,with_driver'],
            'scheduled_start_at'  => ['required', 'date'],
            'scheduled_end_at'    => ['required', 'date', 'after:scheduled_start_at'],
            'pickup_location'     => ['nullable', 'string', 'max:255'],
            'return_location'     => ['nullable', 'string', 'max:255'],
            'rate_per_day'        => ['required', 'numeric', 'min:0'],
            'estimated_days'      => ['required', 'numeric', 'min:0.01'],
            'deposit_amount'      => ['required', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string'],
            'metadata'            => ['nullable', 'array'],
        ];
    }
}
