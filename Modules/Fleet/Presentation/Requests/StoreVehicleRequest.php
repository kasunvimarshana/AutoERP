<?php

namespace Modules\Fleet\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:30'],
            'make'         => ['required', 'string', 'max:100'],
            'model'        => ['required', 'string', 'max:100'],
            'year'         => ['required', 'integer', 'min:1900', 'max:2100'],
            'color'        => ['nullable', 'string', 'max:50'],
            'fuel_type'    => ['nullable', 'in:petrol,diesel,electric,hybrid,lpg'],
            'vin'          => ['nullable', 'string', 'max:50'],
            'assigned_to'  => ['nullable', 'uuid'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
