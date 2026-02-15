<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Procurement\Models\PurchaseReceipt::class);
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'code' => ['nullable', 'string', 'max:50', 'unique:purchase_receipts,code'],
            'receipt_date' => ['required', 'date'],
            'delivery_note_number' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'quality_check_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.ordered_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.received_quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.accepted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.lot_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date', 'after:today'],
            'items.*.manufacturing_date' => ['nullable', 'date', 'before_or_equal:today'],
            'items.*.quality_status' => ['nullable', 'in:passed,failed,pending'],
            'items.*.rejection_reason' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
