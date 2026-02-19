<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockCountItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('stockCount'));
    }

    public function rules(): array
    {
        $tenantId = $this->user()->currentTenant()->id;

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                Rule::exists('stock_count_items', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'items.*.counted_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.id' => 'item',
            'items.*.counted_quantity' => 'counted quantity',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item must be provided.',
            'items.min' => 'At least one item must be provided.',
            'items.*.id.required' => 'Item ID is required.',
            'items.*.id.exists' => 'Invalid item ID.',
            'items.*.counted_quantity.required' => 'Counted quantity is required.',
            'items.*.counted_quantity.numeric' => 'Counted quantity must be a number.',
            'items.*.counted_quantity.min' => 'Counted quantity must be at least 0.',
        ];
    }
}
