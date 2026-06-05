<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class ListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.(int) config('item.pagination.max_per_page', 200),
            ],
            'search' => ['sometimes', 'nullable', 'string', 'max:180'],
            'status' => ['sometimes', 'nullable', Rule::in(['active', 'inactive'])],
            'organization_unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId),
            ],
            'sort_by' => [
                'sometimes',
                Rule::in(['item_code', 'name', 'item_type', 'cost_price', 'sales_price', 'status', 'created_at', 'updated_at']),
            ],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
