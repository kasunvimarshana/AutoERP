<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $po = \App\Modules\Procurement\Models\PurchaseOrder::find($this->route('id'));
        return $po && $this->user()->can('update', $po);
    }

    public function rules(): array
    {
        $poId = $this->route('id');
        
        return [
            'vendor_id' => ['sometimes', 'integer', 'exists:vendors,id'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:purchase_orders,code,' . $poId],
            'reference_number' => ['sometimes', 'string', 'max:100'],
            'order_date' => ['sometimes', 'date'],
            'expected_delivery_date' => ['sometimes', 'date'],
            'location_id' => ['sometimes', 'integer', 'exists:locations,id'],
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id'],
            'exchange_rate' => ['sometimes', 'numeric', 'min:0'],
            'discount_type' => ['sometimes', 'in:flat,percentage'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'discount_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'shipping_amount' => ['sometimes', 'numeric', 'min:0'],
            'other_charges' => ['sometimes', 'numeric', 'min:0'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0'],
            'payment_method' => ['sometimes', 'in:cash,credit_card,bank_transfer,cheque,other'],
            'shipping_method' => ['sometimes', 'string', 'max:100'],
            'shipping_address' => ['sometimes', 'string'],
            'billing_address' => ['sometimes', 'string'],
            'notes' => ['sometimes', 'string'],
            'internal_notes' => ['sometimes', 'string'],
            'terms_conditions' => ['sometimes', 'string'],
            'tags' => ['sometimes', 'array'],
            'custom_fields' => ['sometimes', 'array'],
            
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:flat,percentage'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
