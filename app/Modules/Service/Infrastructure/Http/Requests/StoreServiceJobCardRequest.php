<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceJobCardRequest extends FormRequest
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
            'job_number' => ['required', 'string', 'max:50'],
            'asset_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'maintenance_plan_id' => ['nullable', 'integer'],
            'service_type' => ['required', 'string', 'in:preventive,corrective,inspection,warranty,emergency'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'status' => ['nullable', 'string', 'in:open,in_progress,waiting_parts,on_hold,completed,cancelled'],
            'scheduled_at' => ['nullable', 'date'],
            'odometer_in' => ['nullable', 'numeric'],
            'is_billable' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'integer'],
            'diagnosis' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
