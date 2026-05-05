<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id'          => ['nullable', 'integer', 'min:1'],
            'pickup_location'    => ['nullable', 'string', 'max:255'],
            'return_location'    => ['nullable', 'string', 'max:255'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at'   => ['nullable', 'date'],
            'rate_per_day'       => ['nullable', 'numeric', 'min:0'],
            'estimated_days'     => ['nullable', 'numeric', 'min:0.01'],
            'deposit_amount'     => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
