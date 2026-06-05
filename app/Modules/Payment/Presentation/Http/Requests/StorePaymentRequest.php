<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();

        return [
            'organization_unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId)],
            'payment_number' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('payments', 'payment_number')->where('tenant_id', $tenantId)],
            'party_type' => ['sometimes', Rule::in(['customer', 'supplier'])],
            'party_id' => ['required', 'integer'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'direction' => ['required', Rule::in(['inbound', 'outbound'])],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'account_id' => ['sometimes', 'nullable', 'integer', Rule::exists('accounts', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'currency_id' => ['sometimes', 'nullable', 'integer', Rule::exists('currencies', 'id')],
            'exchange_rate' => ['sometimes', 'numeric', 'gt:0'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'idempotency_key' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('payments', 'idempotency_key')->where('tenant_id', $tenantId)],
            'allocations' => ['sometimes', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'allocations.*.allocated_amount' => ['required_with:allocations', 'numeric', 'gt:0'],
            'allocations.*.allocation_date' => ['sometimes', 'nullable', 'date'],
            'allocations.*.reference' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
