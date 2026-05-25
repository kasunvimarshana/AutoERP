<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTransferOrderRequest extends FormRequest
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
            'from_warehouse_id' => array_merge($required, ['integer', 'min:1', 'exists:warehouses,id']),
            'to_warehouse_id' => array_merge($required, ['integer', 'min:1', 'exists:warehouses,id']),
            'transfer_number' => array_merge($required, ['string', 'max:255']),
            'status' => ['nullable', 'string', 'max:255'],
            'request_date' => array_merge($required, ['date']),
            'expected_date' => ['nullable', 'date'],
            'shipped_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}