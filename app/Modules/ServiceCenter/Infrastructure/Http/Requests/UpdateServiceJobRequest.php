<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_type'           => ['sometimes', 'in:maintenance,repair,inspection,cleaning,tyre,other'],
            'scheduled_at'       => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'started_at'         => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'completed_at'       => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'odometer_in'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'odometer_out'       => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'description'        => ['sometimes', 'nullable', 'string'],
            'parts_cost'         => ['sometimes', 'numeric', 'min:0'],
            'labour_cost'        => ['sometimes', 'numeric', 'min:0'],
            'total_cost'         => ['sometimes', 'numeric', 'min:0'],
            'technician_notes'   => ['sometimes', 'nullable', 'string'],
            'customer_approval'  => ['sometimes', 'boolean'],
            'metadata'           => ['sometimes', 'nullable', 'array'],
        ];
    }
}
