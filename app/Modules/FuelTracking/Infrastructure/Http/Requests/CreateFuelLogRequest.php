<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFuelLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'log_number'       => ['required', 'string', 'max:64'],
            'vehicle_id'       => ['required', 'string', 'uuid'],
            'driver_id'        => ['nullable', 'string', 'uuid'],
            'fuel_type'        => ['required', 'string', 'in:petrol,diesel,electric,hybrid,lpg,other'],
            'odometer_reading' => ['required', 'numeric', 'min:0'],
            'litres'           => ['required', 'numeric', 'min:0'],
            'cost_per_litre'   => ['required', 'numeric', 'min:0'],
            'total_cost'       => ['required', 'numeric', 'min:0'],
            'station_name'     => ['nullable', 'string', 'max:255'],
            'filled_at'        => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
