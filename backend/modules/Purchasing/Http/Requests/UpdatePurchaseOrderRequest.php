<?php

declare(strict_types=1);

namespace Modules\Purchasing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement permission check
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['sometimes', 'integer', 'exists:suppliers,id'],
            'order_date' => ['sometimes', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after:order_date'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
