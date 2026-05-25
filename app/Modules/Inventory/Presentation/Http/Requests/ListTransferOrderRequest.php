<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListTransferOrderRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('inventory.pagination.max_per_page', 200)],
            'from_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'to_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'transfer_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'request_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'shipped_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
        ];
    }
}