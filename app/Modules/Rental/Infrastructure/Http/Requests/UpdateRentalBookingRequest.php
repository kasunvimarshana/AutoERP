<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRentalBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->header('X-Tenant-ID');

        return [
            'customer_id' => ['sometimes', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'rental_mode' => ['sometimes', 'string', Rule::in(['with_driver', 'without_driver'])],
            'ownership_model' => ['sometimes', 'string', Rule::in(['owned_fleet', 'third_party', 'mixed'])],
            'pickup_at' => ['sometimes', 'date'],
            'return_due_at' => ['sometimes', 'date', 'after:pickup_at'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'return_location' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id'],
            'rate_plan' => ['sometimes', 'string', Rule::in(['hourly', 'daily', 'weekly', 'monthly', 'custom'])],
            'rate_amount' => ['sometimes', 'numeric', 'min:0'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'security_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'partner_supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)],
            'terms_and_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
