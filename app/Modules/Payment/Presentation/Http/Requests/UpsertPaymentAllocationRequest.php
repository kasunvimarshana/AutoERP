<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPaymentAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'payment_id' => array_merge($required, ['integer', 'min:1', 'exists:payments,id']),
            'document_type' => array_merge($required, ['string', 'max:255']),
            'document_id' => array_merge($required, ['integer', 'min:1']),
            'document_line_id' => ['nullable', 'integer', 'min:1'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_context' => ['nullable', 'array'],
            'reference' => ['nullable', 'string', 'max:255'],
            'allocated_amount' => array_merge($required, ['numeric', 'gt:0']),
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'base_allocated_amount' => ['nullable', 'numeric', 'gt:0'],
            'allocation_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,reversed'],
        ];
    }
}
