<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Procurement\Models\PurchaseReturn::class);
    }

    public function rules(): array
    {
        return [
            'purchase_receipt_id' => ['required', 'integer', 'exists:purchase_receipts,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'code' => ['nullable', 'string', 'max:50', 'unique:purchase_returns,code'],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'restocking_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_receipt_item_id' => ['required', 'integer', 'exists:purchase_receipt_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.lot_number' => ['nullable', 'string', 'max:100'],
            'items.*.reason' => ['required', 'string'],
            'items.*.condition' => ['nullable', 'in:damaged,defective,expired,wrong_item,other'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
