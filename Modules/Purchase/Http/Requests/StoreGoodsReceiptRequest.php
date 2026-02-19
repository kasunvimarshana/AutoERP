<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Purchase\Models\GoodsReceipt::class);
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'purchase_order_id' => [
                'required',
                Rule::exists('purchase_orders', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'receipt_date' => ['required', 'date'],
            'delivery_note' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => [
                'required',
                Rule::exists('purchase_order_items', 'id')
                    ->whereNull('deleted_at'),
            ],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'items.*.unit_id' => [
                'required',
                Rule::exists('units', 'id')
                    ->whereNull('deleted_at'),
            ],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'items.*.quantity_accepted' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'purchase_order_id' => 'purchase order',
            'receipt_date' => 'receipt date',
            'delivery_note' => 'delivery note',
            'items.*.purchase_order_item_id' => 'purchase order item',
            'items.*.product_id' => 'product',
            'items.*.unit_id' => 'unit',
            'items.*.quantity_received' => 'quantity received',
            'items.*.quantity_accepted' => 'quantity accepted',
        ];
    }
}
