<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Goods Receipt Request
 *
 * Validates data for updating a goods receipt.
 */
class UpdateGoodsReceiptRequest extends FormRequest
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
        $receiptId = $this->route('id');

        return [
            'receipt_number' => ['nullable', 'string', 'max:50', 'unique:goods_receipts,receipt_number,'.$receiptId],
            'purchase_order_id' => ['sometimes', 'required', 'integer', 'exists:purchase_orders,id'],
            'received_date' => ['sometimes', 'required', 'date'],
            'received_by' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:draft,confirmed,cancelled'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
