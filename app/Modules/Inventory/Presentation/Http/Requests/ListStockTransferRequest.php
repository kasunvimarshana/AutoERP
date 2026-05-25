<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListStockTransferRequest extends FormRequest
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
            'reference_number' => ['nullable', 'string', 'max:255'],
            'from_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'to_warehouse_id' => ['nullable', 'integer', 'min:1'],
            'from_location_id' => ['nullable', 'integer', 'min:1'],
            'to_location_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:255'],
            'requested_by' => ['nullable', 'integer', 'min:1'],
            'approved_by' => ['nullable', 'integer', 'min:1'],
            'transferred_at' => ['nullable', 'date'],
        ];
    }
}