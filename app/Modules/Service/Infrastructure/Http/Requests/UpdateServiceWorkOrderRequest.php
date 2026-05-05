<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->header('X-Tenant-ID');

        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'assigned_team_org_unit_id' => ['nullable', 'integer', Rule::exists('org_units', 'id')->where('tenant_id', $tenantId)],
            'service_type' => ['sometimes', 'string', Rule::in(['preventive', 'corrective', 'inspection', 'warranty', 'internal'])],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'normal', 'high', 'critical'])],
            'billing_mode' => ['sometimes', 'string', Rule::in(['customer_billable', 'warranty', 'internal_cost', 'rental_intercompany'])],
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date'],
            'meter_in' => ['nullable', 'numeric', 'min:0'],
            'meter_unit' => ['nullable', 'string', 'max:12'],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
