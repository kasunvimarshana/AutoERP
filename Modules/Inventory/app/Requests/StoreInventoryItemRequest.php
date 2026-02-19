<?php

declare(strict_types=1);

namespace Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Inventory Item Request
 *
 * Validates data for creating a new inventory item
 */
class StoreInventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'item_code' => ['required', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'unit_of_measure' => ['required', 'string', 'max:20'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity' => ['sometimes', 'integer', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock_on_hand' => ['sometimes', 'integer', 'min:0'],
            'is_dummy_item' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'branch',
            'item_code' => 'item code',
            'item_name' => 'item name',
            'unit_of_measure' => 'unit of measure',
            'reorder_level' => 'reorder level',
            'reorder_quantity' => 'reorder quantity',
            'unit_cost' => 'unit cost',
            'selling_price' => 'selling price',
            'stock_on_hand' => 'stock on hand',
            'is_dummy_item' => 'dummy item flag',
        ];
    }
}
