<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Purchase Order Line Request
 *
 * Validates data for updating a purchase order line.
 */
class UpdatePurchaseOrderLineRequest extends FormRequest
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
            'purchase_order_id' => ['sometimes', 'required', 'integer', 'exists:purchase_orders,id'],
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'description' => ['nullable', 'string'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'unit_of_measure' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
