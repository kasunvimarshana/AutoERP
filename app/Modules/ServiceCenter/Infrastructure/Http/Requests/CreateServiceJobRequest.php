<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_unit_id'        => ['nullable', 'integer', 'min:1'],
            'vehicle_id'         => ['required', 'integer', 'min:1'],
            'driver_id'          => ['nullable', 'integer', 'min:1'],
            'job_number'         => ['required', 'string', 'max:100'],
            'job_type'           => ['required', 'in:maintenance,repair,inspection,cleaning,tyre,other'],
            'scheduled_at'       => ['required', 'date_format:Y-m-d H:i:s'],
            'started_at'         => ['nullable', 'date_format:Y-m-d H:i:s'],
            'completed_at'       => ['nullable', 'date_format:Y-m-d H:i:s'],
            'odometer_in'        => ['nullable', 'numeric', 'min:0'],
            'odometer_out'       => ['nullable', 'numeric', 'min:0'],
            'description'        => ['nullable', 'string'],
            'parts_cost'         => ['required', 'numeric', 'min:0'],
            'labour_cost'        => ['required', 'numeric', 'min:0'],
            'total_cost'         => ['required', 'numeric', 'min:0'],
            'technician_notes'   => ['nullable', 'string'],
            'customer_approval'  => ['nullable', 'boolean'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
