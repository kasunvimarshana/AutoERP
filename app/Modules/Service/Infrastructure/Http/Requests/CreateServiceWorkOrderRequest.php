<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->header('X-Tenant-ID');

        return [
            'org_unit_id' => ['nullable', 'integer', Rule::exists('org_units', 'id')->where('tenant_id', $tenantId)],
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where('tenant_id', $tenantId)],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'assigned_team_org_unit_id' => ['nullable', 'integer', Rule::exists('org_units', 'id')->where('tenant_id', $tenantId)],
            'service_type' => ['required', 'string', Rule::in(['preventive', 'corrective', 'inspection', 'warranty', 'internal'])],
            'priority' => ['nullable', 'string', Rule::in(['low', 'normal', 'high', 'critical'])],
            'billing_mode' => ['nullable', 'string', Rule::in(['customer_billable', 'warranty', 'internal_cost', 'rental_intercompany'])],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'opened_at' => ['nullable', 'date'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date', 'after_or_equal:scheduled_start_at'],
            'meter_in' => ['nullable', 'numeric', 'min:0'],
            'meter_unit' => ['nullable', 'string', 'max:12'],
            'symptoms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
