<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListGrnLineRequest extends FormRequest
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
            'grn_header_id' => ['nullable', 'integer', 'min:1', 'exists:grn_headers,id'],
            'purchase_order_line_id' => ['nullable', 'integer', 'min:1', 'exists:purchase_order_lines,id'],
            'item_id' => ['nullable', 'integer', 'min:1', 'exists:items,id']
        ];
    }
}