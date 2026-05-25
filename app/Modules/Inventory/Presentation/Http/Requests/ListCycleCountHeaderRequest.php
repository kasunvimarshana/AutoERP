<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListCycleCountHeaderRequest extends FormRequest
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
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:255'],
            'counted_by_user_id' => ['nullable', 'integer', 'min:1'],
            'counted_at' => ['nullable', 'date'],
            'approved_by_user_id' => ['nullable', 'integer', 'min:1'],
            'approved_at' => ['nullable', 'date'],
        ];
    }
}