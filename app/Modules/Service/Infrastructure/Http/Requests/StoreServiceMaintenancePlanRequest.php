<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceMaintenancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'org_unit_id' => ['nullable', 'integer'],
            'plan_code' => ['required', 'string', 'max:50'],
            'plan_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'asset_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'trigger_type' => ['required', 'string', 'in:time_based,odometer_based,hours_based,event_based,combined'],
            'interval_days' => ['nullable', 'integer', 'min:1'],
            'interval_km' => ['nullable', 'numeric'],
            'interval_hours' => ['nullable', 'numeric'],
            'advance_notice_days' => ['nullable', 'integer', 'min:0'],
            'assigned_employee_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
