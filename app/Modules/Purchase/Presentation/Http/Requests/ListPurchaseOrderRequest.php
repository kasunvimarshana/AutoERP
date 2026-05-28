<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListPurchaseOrderRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('purchase.pagination.max_per_page', 200)],
            'supplier_id' => ['nullable', 'integer', 'min:1', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'document_status' => ['nullable', 'string', 'max:255']
        ];
    }
}
