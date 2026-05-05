<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:500'],
            'base_daily_rate'  => ['nullable', 'numeric', 'min:0'],
            'base_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'seating_capacity' => ['nullable', 'integer', 'min:1'],
            'org_unit_id'      => ['nullable', 'integer'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }
}
