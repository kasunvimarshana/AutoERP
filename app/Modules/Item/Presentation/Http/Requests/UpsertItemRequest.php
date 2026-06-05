<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertItemRequest extends FormRequest
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
        $itemId = $this->route('item');
        $required = $this->isMethod('patch') ? ['sometimes'] : ['required'];
        $tenantUom = fn () => Rule::exists('unit_of_measures', 'id')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        return [
            'organization_unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId),
            ],
            'item_code' => [
                ...$required,
                'string',
                'max:60',
                Rule::unique('items', 'item_code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($itemId),
            ],
            'name' => [...$required, 'string', 'max:180'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:180'],
            'item_type' => ['sometimes', 'nullable', Rule::in(['inventory', 'service', 'non_inventory'])],
            'base_uom_id' => [...$required, 'integer', $tenantUom()],
            'purchase_uom_id' => ['sometimes', 'nullable', 'integer', $tenantUom()],
            'sales_uom_id' => ['sometimes', 'nullable', 'integer', $tenantUom()],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('items', 'barcode')
                    ->where('tenant_id', $tenantId)
                    ->ignore($itemId),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'track_inventory' => ['sometimes', 'boolean'],
            'is_stock_item' => ['sometimes', 'boolean'],
            'is_service_item' => ['sometimes', 'boolean'],
            'cost_price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'sales_price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'reorder_quantity' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'status' => [...$required, Rule::in(['active', 'inactive'])],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
