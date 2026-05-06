<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRentalBookingRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'rental_mode' => ['required', 'string', Rule::in(['with_driver', 'without_driver'])],
            'ownership_model' => ['nullable', 'string', Rule::in(['owned_fleet', 'third_party', 'mixed'])],
            'pickup_at' => ['required', 'date'],
            'return_due_at' => ['required', 'date', 'after:pickup_at'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'return_location' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'rate_plan' => ['required', 'string', Rule::in(['hourly', 'daily', 'weekly', 'monthly', 'custom'])],
            'rate_amount' => ['required', 'numeric', 'min:0'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'security_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'partner_supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)],
            'terms_and_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['required', 'integer', Rule::exists('assets', 'id')->where('tenant_id', $tenantId)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
