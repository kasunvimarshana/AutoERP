<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class AllocatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();

        return [
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'gt:0'],
            'allocations.*.allocation_date' => ['sometimes', 'nullable', 'date'],
            'allocations.*.reference' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
