<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceLaborEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'min:1'],
            'org_unit_id' => ['sometimes', 'integer', 'min:1'],
            'service_task_id' => ['sometimes', 'integer', 'min:1'],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'ended_at' => ['sometimes', 'nullable', 'date'],
            'hours_worked' => ['required', 'numeric', 'min:0'],
            'labor_rate' => ['sometimes', 'numeric', 'min:0'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0'],
            'incentive_amount' => ['sometimes', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
