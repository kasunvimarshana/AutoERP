<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_unit_id'        => ['nullable', 'integer'],
            'employee_id'        => ['nullable', 'integer'],
            'driver_code'        => ['required', 'string', 'max:50'],
            'full_name'          => ['required', 'string', 'max:200'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'email'              => ['nullable', 'email', 'max:200'],
            'address'            => ['nullable', 'string'],
            'compensation_type'  => ['required', 'in:salary,per_trip,commission'],
            'per_trip_rate'      => ['nullable', 'numeric', 'min:0'],
            'commission_pct'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
